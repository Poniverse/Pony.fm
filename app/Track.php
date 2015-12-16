<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Peter Deltchev
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Poniverse\Ponyfm;

use Auth;
use Cache;
use Config;
use DB;
use Poniverse\Ponyfm\Traits\SlugTrait;
use Exception;
use External;
use getid3_writetags;
use Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Log;
use URL;
use Venturecraft\Revisionable\RevisionableTrait;

class Track extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    use SlugTrait {
        SlugTrait::setTitleAttribute as setTitleAttributeSlug;
    }

    use RevisionableTrait;

    public static $Formats = [
        'FLAC' => [
            'index' => 0,
            'is_lossless' => true,
            'extension' => 'flac',
            'tag_format' => 'metaflac',
            'tag_method' => 'updateTagsWithGetId3',
            'mime_type' => 'audio/flac',
            'command' => 'ffmpeg 2>&1 -y -i {$source} -acodec flac -aq 8 -f flac {$target}'
        ],
        'MP3' => [
            'index' => 1,
            'is_lossless' => false,
            'extension' => 'mp3',
            'tag_format' => 'id3v2.3',
            'tag_method' => 'updateTagsWithGetId3',
            'mime_type' => 'audio/mpeg',
            'command' => 'ffmpeg 2>&1 -y -i {$source} -acodec libmp3lame -ab 320k -f mp3 {$target}'
        ],
        'OGG Vorbis' => [
            'index' => 2,
            'is_lossless' => false,
            'extension' => 'ogg',
            'tag_format' => 'vorbiscomment',
            'tag_method' => 'updateTagsWithGetId3',
            'mime_type' => 'audio/ogg',
            'command' => 'ffmpeg 2>&1 -y -i {$source} -acodec libvorbis -aq 7 -f ogg {$target}'
        ],
        'AAC' => [
            'index' => 3,
            'is_lossless' => false,
            'extension' => 'm4a',
            'tag_format' => 'AtomicParsley',
            'tag_method' => 'updateTagsWithAtomicParsley',
            'mime_type' => 'audio/mp4',
            'command' => 'ffmpeg 2>&1 -y -i {$source} -acodec libfaac -ab 256k -f mp4 {$target}'
        ],
        'ALAC' => [
            'index' => 4,
            'is_lossless' => true,
            'extension' => 'alac.m4a',
            'tag_format' => 'AtomicParsley',
            'tag_method' => 'updateTagsWithAtomicParsley',
            'mime_type' => 'audio/mp4',
            'command' => 'ffmpeg 2>&1 -y -i {$source} -acodec alac {$target}'
        ],
    ];

    /**
     * `TrackFiles` in these formats, with the exception of any master files, will
     * be generated upon user request and kept around temporarily.
     *
     * After updating this array, run `php artisan rebuild:track-cache` to bring
     * the track store into a consistent state.
     *
     * The strings in this array must match keys in the `Track::$Formats` array.
     *
     * @var array
     */
    public static $CacheableFormats = [
        'OGG Vorbis',
        'ALAC',
        'AAC'
    ];

    public static function summary()
    {
        return self::select('tracks.id', 'title', 'user_id', 'slug', 'is_vocal', 'is_explicit', 'created_at',
            'published_at',
            'duration', 'is_downloadable', 'genre_id', 'track_type_id', 'cover_id', 'album_id', 'comment_count',
            'download_count', 'view_count', 'play_count', 'favourite_count');
    }

    public function scopeUserDetails($query)
    {
        if (Auth::check()) {
            $query->with([
                'users' => function ($query) {
                    $query->whereUserId(Auth::user()->id);
                }
            ]);
        }
    }

    public function scopePublished($query)
    {
        $query->whereNotNull('published_at');
    }

    public function scopeListed($query)
    {
        $query->whereIsListed(true);
    }

    public function scopeExplicitFilter($query)
    {
        if (!Auth::check() || !Auth::user()->can_see_explicit_content) {
            $query->whereIsExplicit(false);
        }
    }

    public function scopeWithComments($query)
    {
        $query->with([
            'comments' => function ($query) {
                $query->with('user');
            }
        ]);
    }

    /**
     * Limits results to MLP Music Archive tracks.
     *
     * @param $query
     */
    public function scopeMlpma($query)
    {
        $query->join('mlpma_tracks', 'tracks.id', '=', 'mlpma_tracks.track_id');
    }

    public static function popular($count, $allowExplicit = false)
    {
        $trackIds = Cache::remember('popular_tracks' . $count . '-' . ($allowExplicit ? 'explicit' : 'safe'), 5,
            function () use ($allowExplicit, $count) {
                $query = static
                    ::published()
                    ->listed()
                    ->join(DB::raw('
                        (    SELECT `track_id`, `created_at`
                            FROM `resource_log_items`
                            WHERE track_id IS NOT NULL AND log_type = 3 AND `created_at` > now() - INTERVAL 1 DAY
                        ) AS ranged_plays'),
                        'tracks.id', '=', 'ranged_plays.track_id')
                    ->groupBy('id')
                    ->orderBy('plays', 'desc')
                    ->take($count);

                if (!$allowExplicit) {
                    $query->whereIsExplicit(false);
                }

                $results = [];

                foreach ($query->get(['*', DB::raw('count(*) as plays')]) as $track) {
                    $results[] = $track->id;
                }

                return $results;
            });

        if (!count($trackIds)) {
            return [];
        }

        $tracks = Track::summary()
            ->userDetails()
            ->explicitFilter()
            ->published()
            ->with('user', 'genre', 'cover', 'album', 'album.user')
            ->whereIn('id', $trackIds);

        $processed = [];
        foreach ($tracks->get() as $track) {
            $processed[] = Track::mapPublicTrackSummary($track);
        }

        return $processed;
    }

    public static function mapPublicTrackShow(Track $track)
    {
        $returnValue = self::mapPublicTrackSummary($track);
        $returnValue['description'] = $track->description;
        $returnValue['lyrics'] = $track->lyrics;

        $comments = [];

        foreach ($track->comments as $comment) {
            $comments[] = Comment::mapPublic($comment);
        }

        $returnValue['comments'] = $comments;

        if ($track->album_id != null) {
            $returnValue['album'] = [
                'title' => $track->album->title,
                'url' => $track->album->url,
            ];
        }

        $formats = [];

        foreach ($track->trackFiles as $trackFile) {
            $formats[] = [
                'name' => $trackFile->format,
                'extension' => $trackFile->extension,
                'url' => $trackFile->url,
                'size' => $trackFile->size,
                'isCacheable' => (bool) $trackFile->is_cacheable
            ];
        }

        $returnValue['share'] = [
            'url' => URL::to('/t' . $track->id),
            'html' => '<iframe src="' . URL::to('t' . $track->id . '/embed') . '" width="100%" height="150" allowTransparency="true" frameborder="0" seamless allowfullscreen></iframe>',
            'bbcode' => '[url=' . $track->url . '][img]' . $track->getCoverUrl() . '[/img][/url]',
            'twitterUrl' => 'https://platform.twitter.com/widgets/tweet_button.html?text=' . $track->title . ' by ' . $track->user->display_name . ' on Pony.fm'
        ];

        $returnValue['share']['tumblrUrl'] = 'http://www.tumblr.com/share/video?embed=' . urlencode($returnValue['share']['html']) . '&caption=' . urlencode($track->title);

        $returnValue['formats'] = $formats;

        return $returnValue;
    }

    public static function mapPublicTrackSummary(Track $track)
    {
        $userData = [
            'stats' => [
                'views' => 0,
                'plays' => 0,
                'downloads' => 0
            ],
            'is_favourited' => false
        ];

        if (Auth::check() && $track->users->count()) {
            $userRow = $track->users[0];
            $userData = [
                'stats' => [
                    'views' => (int)$userRow->view_count,
                    'plays' => (int)$userRow->play_count,
                    'downloads' => $userRow->download_count,
                ],
                'is_favourited' => (bool)$userRow->is_favourited
            ];
        }

        return [
            'id' => (int)$track->id,
            'title' => $track->title,
            'user' => [
                'id' => (int)$track->user->id,
                'name' => $track->user->display_name,
                'url' => $track->user->url
            ],
            'stats' => [
                'views' => (int)$track->view_count,
                'plays' => (int)$track->play_count,
                'downloads' => (int)$track->download_count,
                'comments' => (int)$track->comment_count,
                'favourites' => (int)$track->favourite_count
            ],
            'url' => $track->url,
            'slug' => $track->slug,
            'is_vocal' => (bool)$track->is_vocal,
            'is_explicit' => (bool)$track->is_explicit,
            'is_downloadable' => (bool)$track->is_downloadable,
            'is_published' => (bool)$track->isPublished(),
            'published_at' => $track->published_at->format('c'),
            'duration' => $track->duration,
            'genre' => $track->genre != null
                ?
                [
                    'id' => (int)$track->genre->id,
                    'slug' => $track->genre->slug,
                    'name' => $track->genre->name
                ] : null,
            'track_type_id' => $track->track_type_id,
            'covers' => [
                'thumbnail' => $track->getCoverUrl(Image::THUMBNAIL),
                'small' => $track->getCoverUrl(Image::SMALL),
                'normal' => $track->getCoverUrl(Image::NORMAL),
                'original' => $track->getCoverUrl(Image::ORIGINAL)
            ],
            'streams' => [
                'mp3' => $track->getStreamUrl('MP3'),
                'aac' => (!Config::get('app.debug') || is_file($track->getFileFor('AAC'))) ? $track->getStreamUrl('AAC') : null,
                'ogg' => (Config::get('app.debug') || is_file($track->getFileFor('OGG Vorbis'))) ? $track->getStreamUrl('OGG Vorbis') : null
            ],
            'user_data' => $userData,
            'permissions' => [
                'delete' => Auth::check() && Auth::user()->id == $track->user_id,
                'edit' => Auth::check() && Auth::user()->id == $track->user_id
            ]
        ];
    }

    public static function mapPrivateTrackShow(Track $track)
    {
        $showSongs = [];
        foreach ($track->showSongs as $showSong) {
            $showSongs[] = ['id' => $showSong->id, 'title' => $showSong->title];
        }

        $returnValue = self::mapPrivateTrackSummary($track);
        $returnValue['album_id'] = $track->album_id;
        $returnValue['show_songs'] = $showSongs;
        $returnValue['real_cover_url'] = $track->getCoverUrl(Image::NORMAL);
        $returnValue['cover_url'] = $track->hasCover() ? $track->getCoverUrl(Image::NORMAL) : null;
        $returnValue['released_at'] = $track->released_at;
        $returnValue['lyrics'] = $track->lyrics;
        $returnValue['description'] = $track->description;
        $returnValue['is_downloadable'] = !$track->isPublished() ? true : (bool)$track->is_downloadable;
        $returnValue['license_id'] = $track->license_id != null ? $track->license_id : 3;

        return $returnValue;
    }

    public static function mapPrivateTrackSummary(Track $track)
    {
        return [
            'id' => $track->id,
            'title' => $track->title,
            'user_id' => $track->user_id,
            'slug' => $track->slug,
            'is_vocal' => $track->is_vocal,
            'is_explicit' => $track->is_explicit,
            'is_downloadable' => $track->is_downloadable,
            'is_published' => $track->isPublished(),
            'created_at' => $track->created_at->format('c'),
            'published_at' => $track->published_at->format('c'),
            'duration' => $track->duration,
            'genre_id' => $track->genre_id,
            'track_type_id' => $track->track_type_id,
            'cover_url' => $track->getCoverUrl(Image::SMALL),
            'is_listed' => !!$track->is_listed
        ];
    }

    protected $table = 'tracks';

    public function genre()
    {
        return $this->belongsTo('Poniverse\Ponyfm\Genre');
    }

    public function trackType()
    {
        return $this->belongsTo('Poniverse\Ponyfm\TrackType', 'track_type_id');
    }

    public function comments()
    {
        return $this->hasMany('Poniverse\Ponyfm\Comment')->orderBy('created_at', 'desc');
    }

    public function favourites()
    {
        return $this->hasMany('Poniverse\Ponyfm\Favourite');
    }

    public function cover()
    {
        return $this->belongsTo('Poniverse\Ponyfm\Image');
    }

    public function showSongs()
    {
        return $this->belongsToMany('Poniverse\Ponyfm\ShowSong');
    }

    public function users()
    {
        return $this->hasMany('Poniverse\Ponyfm\ResourceUser');
    }

    public function user()
    {
        return $this->belongsTo('Poniverse\Ponyfm\User');
    }

    public function album()
    {
        return $this->belongsTo('Poniverse\Ponyfm\Album');
    }

    public function trackFiles()
    {
        return $this->hasMany('Poniverse\Ponyfm\TrackFile');
    }

    public function getYearAttribute()
    {
        return date('Y', strtotime($this->getReleaseDate()));
    }

    public function setTitleAttribute($value)
    {
        $this->setTitleAttributeSlug($value);;
        $this->updateHash();
    }

    /**
     * Returns the size of this track's file in the given format.
     *
     * @param $formatName
     * @return int filesize in bytes
     * @throws TrackFileNotFoundException
     */
    public function getFilesize($formatName)
    {
        $trackFile = $this->trackFiles()->where('format', $formatName)->first();

        if ($trackFile) {
            return (int) $trackFile->filesize;
        } else {
            throw new TrackFileNotFoundException();
        }
    }

    public function canView($user)
    {
        if ($this->isPublished()) {
            return true;
        }

        return $this->user_id == $user->id;
    }

    public function getUrlAttribute()
    {
        return URL::to('/tracks/' . $this->id . '-' . $this->slug);
    }

    public function getDownloadDirectoryAttribute()
    {
        if ($this->album) {
            return $this->user->display_name . '/' . $this->album->title;
        }

        return $this->user->display_name;
    }

    public function getReleaseDate()
    {
        if ($this->released_at !== null) {
            return $this->released_at;
        }

        if ($this->published_at !== null) {
            return Str::limit($this->published_at, 10, '');
        }

        return Str::limit($this->attributes['created_at'], 10, '');
    }

    public function ensureDirectoryExists()
    {
        $destination = $this->getDirectory();
        umask(0);

        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }
    }

    public function hasCover()
    {
        return $this->cover_id != null;
    }

    public function isPublished()
    {
        return $this->published_at != null && $this->deleted_at == null;
    }

    public function getCoverUrl($type = Image::NORMAL)
    {
        if (!$this->hasCover()) {
            if ($this->album_id != null) {
                return $this->album->getCoverUrl($type);
            }

            return $this->user->getAvatarUrl($type);
        }

        return $this->cover->getUrl($type);
    }

    public function getStreamUrl($format = 'MP3')
    {
        return URL::to('/t' . $this->id . '/stream.' . self::$Formats[$format]['extension']);
    }

    public function getDirectory()
    {
        $dir = (string)(floor($this->id / 100) * 100);

        return \Config::get('ponyfm.files_directory') . '/tracks/' . $dir;
    }

    public function getDates()
    {
        return ['created_at', 'deleted_at', 'published_at', 'released_at'];
    }

    public function getFilenameFor($format)
    {
        if (!isset(self::$Formats[$format])) {
            throw new Exception("$format is not a valid format!");
        }

        $format = self::$Formats[$format];

        return "{$this->id}.{$format['extension']}";
    }

    public function getDownloadFilenameFor($format)
    {
        if (!isset(self::$Formats[$format])) {
            throw new Exception("$format is not a valid format!");
        }

        $format = self::$Formats[$format];

        return "{$this->title}.{$format['extension']}";
    }

    public function getFileFor($format)
    {
        if (!isset(self::$Formats[$format])) {
            throw new Exception("$format is not a valid format!");
        }

        $format = self::$Formats[$format];

        return "{$this->getDirectory()}/{$this->id}.{$format['extension']}";
    }

    public function getUrlFor($format)
    {
        if (!isset(self::$Formats[$format])) {
            throw new Exception("$format is not a valid format!");
        }

        $format = self::$Formats[$format];

        return URL::to('/t' . $this->id . '/dl.' . $format['extension']);
    }

    public function updateHash()
    {
        $this->hash = md5(Helpers::sanitizeInputForHashing($this->user->display_name) . ' - ' . Helpers::sanitizeInputForHashing($this->title));
    }

    public function updateTags($trackFileFormat = 'all')
    {
        if ($trackFileFormat === 'all') {
            foreach ($this->trackFiles as $trackFile) {
                $this->updateTagsForTrackFile($trackFile);
            }
        } else {
            $trackFile = $this->trackFiles()->where('format', $trackFileFormat)->firstOrFail();
            $this->updateTagsForTrackFile($trackFile);
        }
    }

    private function updateTagsForTrackFile($trackFile) {
        $trackFile->touch();

        if (\File::exists($trackFile->getFile())) {
            $format = $trackFile->format;
            $data = self::$Formats[$format];

            $this->{$data['tag_method']}($format);
        }
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function updateTagsWithAtomicParsley($format)
    {
        $command = 'AtomicParsley "' . $this->getFileFor($format) . '" ';
        $command .= '--title ' . escapeshellarg($this->title) . ' ';
        $command .= '--artist ' . escapeshellarg($this->user->display_name) . ' ';
        $command .= '--year "' . $this->year . '" ';
        $command .= '--genre ' . escapeshellarg($this->genre != null ? $this->genre->name : '') . ' ';
        $command .= '--copyright ' . escapeshellarg('© ' . $this->year . ' ' . $this->user->display_name) . ' ';
        $command .= '--comment "' . 'Downloaded from: https://pony.fm/' . '" ';
        $command .= '--encodingTool "' . 'Pony.fm - https://pony.fm/' . '" ';

        if ($this->album_id !== null) {
            $command .= '--album ' . escapeshellarg($this->album->title) . ' ';
            $command .= '--tracknum ' . $this->track_number . ' ';
        }

        if ($this->cover !== null) {
            $command .= '--artwork ' . $this->cover->getFile() . ' ';
        }

        $command .= '--overWrite';

        External::execute($command);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function updateTagsWithGetId3($format)
    {
        require_once(app_path() . '/Library/getid3/getid3/getid3.php');
        require_once(app_path() . '/Library/getid3/getid3/write.php');
        $tagWriter = new getid3_writetags;

        $tagWriter->overwrite_tags = true;
        $tagWriter->tag_encoding = 'UTF-8';
        $tagWriter->remove_other_tags = true;

        $tagWriter->tag_data = [
            'title' => [$this->title],
            'artist' => [$this->user->display_name],
            'year' => ['' . $this->year],
            'genre' => [$this->genre != null ? $this->genre->name : ''],
            'comment' => ['Downloaded from: https://pony.fm/'],
            'copyright' => ['© ' . $this->year . ' ' . $this->user->display_name],
            'publisher' => ['Pony.fm - https://pony.fm/'],
            'encoded_by' => ['https://pony.fm/'],
//                'url_artist'            => [$this->user->url],
//                'url_source'            => [$this->url],
//                'url_file'                => [$this->url],
            'url_publisher' => ['https://pony.fm/']
        ];

        if ($this->album_id !== null) {
            $tagWriter->tag_data['album'] = [$this->album->title];
            $tagWriter->tag_data['track'] = [$this->track_number];
        }

        if ($format == 'MP3' && $this->cover_id != null && is_file($this->cover->getFile())) {
            $tagWriter->tag_data['attached_picture'][0] = [
                'data' => file_get_contents($this->cover->getFile()),
                'picturetypeid' => 2,
                'description' => 'cover',
                'mime' => 'image/png'
            ];
        }

        $tagWriter->filename = $this->getFileFor($format);
        $tagWriter->tagformats = [self::$Formats[$format]['tag_format']];

        if ($tagWriter->WriteTags()) {
            if (!empty($tagWriter->warnings)) {
                Log::warning('Track #' . $this->id . ': There were some warnings:<br />' . implode('<br /><br />',
                        $tagWriter->warnings));
            }
        } else {
            Log::error('Track #' . $this->id . ': Failed to write tags!<br />' . implode('<br /><br />',
                    $tagWriter->errors));
        }
    }

    private function getCacheKey($key)
    {
        return 'track-' . $this->id . '-' . $key;
    }
}
