<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015-2017 Feld0.
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

namespace Poniverse\Ponyfm\Models;

use Auth;
use Cache;
use Config;
use DB;
use Exception;
use External;
use Gate;
use getid3_writetags;
use Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Log;
use Poniverse\Ponyfm\Contracts\Commentable;
use Poniverse\Ponyfm\Contracts\Favouritable;
use Poniverse\Ponyfm\Contracts\Searchable;
use Poniverse\Ponyfm\Exceptions\TrackFileNotFoundException;
use Poniverse\Ponyfm\Models\ResourceLogItem;
use Poniverse\Ponyfm\Traits\IndexedInElasticsearchTrait;
use Poniverse\Ponyfm\Traits\SlugTrait;
use Venturecraft\Revisionable\RevisionableTrait;

/**
 * Poniverse\Ponyfm\Models\Track.
 *
 * @property int $id
 * @property int $user_id
 * @property int $license_id
 * @property int $genre_id
 * @property int $track_type_id
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property string $lyrics
 * @property bool $is_vocal
 * @property bool $is_explicit
 * @property int $cover_id
 * @property bool $is_downloadable
 * @property float $duration
 * @property int $play_count
 * @property int $view_count
 * @property int $download_count
 * @property int $favourite_count
 * @property int $comment_count
 * @property \Carbon\Carbon $created_at
 * @property string $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property \Carbon\Carbon $published_at
 * @property \Carbon\Carbon $released_at
 * @property int $album_id
 * @property int $track_number
 * @property bool $is_latest
 * @property string $hash
 * @property bool $is_listed
 * @property string $source
 * @property string $original_tags
 * @property string $metadata
 * @property-read \Poniverse\Ponyfm\Models\Genre $genre
 * @property-read \Poniverse\Ponyfm\Models\TrackType $trackType
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\Comment[] $comments
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\Favourite[] $favourites
 * @property-read \Poniverse\Ponyfm\Models\Image $cover
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\ShowSong[] $showSongs
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\ResourceUser[] $users
 * @property-read \Poniverse\Ponyfm\Models\User $user
 * @property-read \Poniverse\Ponyfm\Models\Album $album
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\TrackFile[] $trackFiles
 * @property-read mixed $year
 * @property-read mixed $url
 * @property-read mixed $download_directory
 * @property-read mixed $status
 * @property-read \Illuminate\Database\Eloquent\Collection|\Venturecraft\Revisionable\Revision[] $revisionHistory
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Track userDetails()
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Track published()
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Track listed()
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Track explicitFilter()
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Track withComments()
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Track mlpma()
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\Activity[] $notifications
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\Activity[] $activities
 * @property int $current_version
 * @property int|null $version_upload_status
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\TrackFile[] $trackFilesForAllVersions
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Track onlyTrashed()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereAlbumId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereCommentCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereCoverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereCurrentVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereDownloadCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereFavouriteCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereGenreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereIsDownloadable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereIsExplicit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereIsLatest($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereIsListed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereIsVocal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereLicenseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereLyrics($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereOriginalTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track wherePlayCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereReleasedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereTrackNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereTrackTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereVersionUploadStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Poniverse\Ponyfm\Models\Track whereViewCount($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Track withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Track withoutTrashed()
 * @mixin \Eloquent
 */
class Track extends Model implements Searchable, Commentable, Favouritable
{
    use SoftDeletes, IndexedInElasticsearchTrait;

    protected $elasticsearchType = 'track';

    protected $dates = ['deleted_at', 'published_at', 'released_at'];
    protected $hidden = ['original_tags', 'metadata'];
    protected $casts = [
        'id'                => 'integer',
        'user_id'           => 'integer',
        'license_id'        => 'integer',
        'album_id'          => 'integer',
        'track_number'      => 'integer',
        'genre_id'          => 'integer',
        'track_type_id'     => 'integer',
        'is_vocal'          => 'boolean',
        'is_explicit'       => 'boolean',
        'cover_id'          => 'integer',
        'is_downloadable'   => 'boolean',
        'is_latest'         => 'boolean',
        'is_listed'         => 'boolean',
        'original_tags'     => 'array',
        'metadata'          => 'array',
    ];

    use SlugTrait {
        SlugTrait::setTitleAttribute as setTitleAttributeSlug;
    }

    use RevisionableTrait;

    // Used for the track's post-upload status. See UploadTrackCommand.
    const STATUS_COMPLETE = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_ERROR = 2;

    /**
     * All the information about how to encode files into Pony.fm's various formats.
     *
     * @var array
     */
    public static $Formats = [
        'FLAC' => [
            'index' => 0,
            'is_lossless' => true,
            'extension' => 'flac',
            'tag_format' => 'metaflac',
            'tag_method' => 'updateTagsWithGetId3',
            'mime_type' => 'audio/flac',
            'command' => 'ffmpeg 2>&1 -y -i {$source} -map 0:a -map_metadata -1 -codec:a flac -aq 8 -f flac {$target}',
        ],
        'MP3' => [
            'index' => 1,
            'is_lossless' => false,
            'extension' => 'mp3',
            'tag_format' => 'id3v2.3',
            'tag_method' => 'updateTagsWithGetId3',
            'mime_type' => 'audio/mpeg',
            'command' => 'ffmpeg 2>&1 -y -i {$source} -map 0:a -map_metadata -1 -codec:a libmp3lame -ab 320k -f mp3 {$target}',
        ],
        'OGG Vorbis' => [
            'index' => 2,
            'is_lossless' => false,
            'extension' => 'ogg',
            'tag_format' => 'vorbiscomment',
            'tag_method' => 'updateTagsWithGetId3',
            'mime_type' => 'audio/ogg',
            'command' => 'ffmpeg 2>&1 -y -i {$source} -map 0:a -map_metadata -1 -codec:a libvorbis -aq 7 -f ogg {$target}',
        ],
        'AAC' => [
            'index' => 3,
            'is_lossless' => false,
            'extension' => 'm4a',
            'tag_format' => 'AtomicParsley',
            'tag_method' => 'updateTagsWithAtomicParsley',
            'mime_type' => 'audio/mp4',
            'command' => 'ffmpeg 2>&1 -y -i {$source} -map 0:a -map_metadata -1 -codec:a libfaac -ab 256k -f mp4 {$target}',
        ],
        'ALAC' => [
            'index' => 4,
            'is_lossless' => true,
            'extension' => 'alac.m4a',
            'tag_format' => 'AtomicParsley',
            'tag_method' => 'updateTagsWithAtomicParsley',
            'mime_type' => 'audio/mp4',
            'command' => 'ffmpeg 2>&1 -y -i {$source} -map 0:a -map_metadata -1 -codec:a alac {$target}',
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
        'AAC',
    ];

    /**
     * Formats in this file are treated specially by Pony.fm as lossless when,
     * for example, generating playlist and album downloads that contain a mix
     * of lossy and lossless tracks.
     *
     * The strings in this array must match keys in the `Track::$Formats` array.
     *
     * @var array
     */
    public static $LosslessFormats = [
        'FLAC',
        'ALAC',
    ];

    /**
     * Prepares a query builder object with typical fields used for a track
     * "tile" around the site and eager-loaded relations that are almost always
     * relevant.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function summary()
    {
        return self::select(
            'tracks.id',
            'title',
            'user_id',
            'slug',
            'is_vocal',
            'is_explicit',
            'created_at',
            'published_at',
            'duration',
            'is_downloadable',
            'genre_id',
            'track_type_id',
            'cover_id',
            'album_id',
            'comment_count',
            'download_count',
            'view_count',
            'play_count',
            'favourite_count'
        )
            ->with('user', 'cover', 'album');
    }

    /**
     * If a user is currently logged in, this scope eager-loads them.
     *
     * @param $query
     */
    public function scopeUserDetails($query)
    {
        if (Auth::check()) {
            $query->with([
                'users' => function ($query) {
                    $query->whereUserId(Auth::user()->id);
                },
            ]);
        }
    }

    /**
     * Limits the query scope to published tracks.
     *
     * @param $query
     */
    public function scopePublished($query)
    {
        $query->whereNotNull('published_at');
    }

    /**
     * Limits the query scope to listed tracks.
     *
     * @param $query
     */
    public function scopeListed($query)
    {
        $query->whereIsListed(true);
    }

    /**
     * Applies the NSFW filter: only allow explicit tracks to come back for
     * this query if a user is logged in and has opted in to them.
     * @param $query
     */
    public function scopeExplicitFilter($query)
    {
        if (! Auth::check() || ! Auth::user()->can_see_explicit_content) {
            $query->whereIsExplicit(false);
        }
    }

    /**
     * Eager-loads track comments with this query.
     *
     * @param $query
     */
    public function scopeWithComments($query)
    {
        $query->with([
            'comments' => function ($query) {
                $query->with('user');
            },
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

    /**
     * The (messy) query used to generate the list of popular tracks on PFM's
     * homepage.
     *
     * @param int $count the number of tracks to return
     * @param bool $allowExplicit whether to include explicit tracks in the results
     * @param int $skip currently unused as of 2017-09-22
     * @return array
     */
    public static function popular($count, $allowExplicit = false, $skip = 0)
    {
        $trackData = Cache::remember(
            'popular_tracks'.$count.'-'.($allowExplicit ? 'explicit' : 'safe'),
            5,
            function () use ($allowExplicit, $count, $skip) {
                /*$query = static
                    ::published()
                    ->listed()
                    ->join(
                        DB::raw('(
                            SELECT "track_id"
                            FROM "resource_log_items"
                            WHERE track_id IS NOT NULL AND log_type = 3 AND "created_at" > now() - INTERVAL \'1\' DAY
                        ) ranged_plays'),
                        'tracks.id',
                        '=',
                        'ranged_plays.track_id'
                    )
                    ->groupBy(['id', 'track_id'])
                    ->orderBy('plays', 'desc')
                    ->skip($skip)
                    ->take($count);

                if (!$allowExplicit) {
                    $query->where('is_explicit', false);
                }

                foreach ($query->get(['*', DB::raw('count(*) as plays')]) as $track) {
                    $results[] = $track->id;
                }*/

                $explicitFilter = '
                    AND NOT EXISTS (
                        SELECT id, is_explicit FROM tracks
                        WHERE track_id = id AND is_explicit = TRUE
                    )';

                if ($allowExplicit) {
                    $explicitFilter = '';
                }

                $queryText = '
                    SELECT track_id,
                    SUM(CASE WHEN log_type = 1 THEN 0.1
                        WHEN log_type = 3 THEN 1
                        WHEN log_type = 2 THEN 2
                        ELSE 0 END) AS weight
                    FROM "resource_log_items"
                    WHERE track_id IS NOT NULL AND log_type IS NOT NULL AND "created_at" > now() - INTERVAL \'1\' DAY
                    '.$explicitFilter.'
                    GROUP BY track_id
                    ORDER BY weight DESC
                    LIMIT '.$count;

                $countQuery = DB::select(DB::raw($queryText));

                $results = [];

                foreach ($countQuery as $track) {
                    $results[] = [
                        'id' => $track->track_id,
                        'weight' => $track->weight,
                    ];
                }

                return $results;
            }
        );

        $trackIds = [];
        $trackWeights = [];

        foreach ($trackData as $track) {
            $trackIds[] = $track['id'];
            $trackWeights[$track['id']] = $track['weight'];
        }

        if (! count($trackIds)) {
            return [];
        }

        $tracks = self::summary()
            ->userDetails()
            ->explicitFilter()
            ->published()
            ->with('user', 'genre', 'cover', 'album', 'album.user')
            ->whereIn('id', $trackIds);

        $processed = [];
        foreach ($tracks->get() as $track) {
            $trackModel = self::mapPublicTrackSummary($track);
            $trackModel['weight'] = $trackWeights[$track->id];
            $processed[] = $trackModel;
        }

        usort($processed, function ($a, $b) {
            return $a['weight'] <=> $b['weight'];
        });

        $processed = array_reverse($processed);

        return $processed;
    }

    /**
     * Brings together all the data displayed on a track page.
     * The data structure this returns is what the web API spits out.
     *
     * @param Track $track
     * @return array
     */
    public static function mapPublicTrackShow(self $track)
    {
        $returnValue = self::mapPublicTrackSummary($track);
        $returnValue['description'] = $track->description;
        $returnValue['lyrics'] = $track->lyrics;

        $comments = [];

        foreach ($track->comments as $comment) {
            $comments[] = Comment::mapPublic($comment);
        }

        $returnValue['comments'] = $comments;

        $formats = [];

        foreach ($track->trackFiles as $trackFile) {
            $formats[] = [
                'name' => $trackFile->format,
                'extension' => $trackFile->extension,
                'url' => $trackFile->url,
                'size' => $trackFile->size,
                'isCacheable' => (bool) $trackFile->is_cacheable,
            ];
        }

        $returnValue['share'] = [
            'url' => action('TracksController@getShortlink', ['id' => $track->id]),
            'html' => '<iframe src="'.action('TracksController@getEmbed', ['id' => $track->id]).'" width="100%" height="150" allowTransparency="true" frameborder="0" seamless allowfullscreen></iframe>',
            'bbcode' => '[url='.$track->url.'][img]'.$track->getCoverUrl().'[/img][/url]',
            'twitterUrl' => 'https://platform.twitter.com/widgets/tweet_button.html?text='.$track->title.' by '.$track->user->display_name.' on Pony.fm',
        ];

        $returnValue['share']['tumblrUrl'] = 'http://www.tumblr.com/share/video?embed='.urlencode($returnValue['share']['html']).'&caption='.urlencode($track->title);

        $returnValue['formats'] = $formats;

        return $returnValue;
    }

    /**
     * Brings together all the data shown on "track tiles" throughout the site.
     * The data structure this returns is what the web API spits out.
     *
     * @param Track $track
     * @return array
     */
    public static function mapPublicTrackSummary(self $track)
    {
        $userData = [
            'stats' => [
                'views' => 0,
                'plays' => 0,
                'downloads' => 0,
            ],
            'is_favourited' => false,
        ];

        if (Auth::check() && $track->users->count()) {
            $userRow = $track->users[0];
            $userData = [
                'stats' => [
                    'views' => (int) $userRow->view_count,
                    'plays' => (int) $userRow->play_count,
                    'downloads' => $userRow->download_count,
                ],
                'is_favourited' => (bool) $userRow->is_favourited,
            ];
        }

        $data = [
            'id' => (int) $track->id,
            'title' => $track->title,
            'user' => [
                'id' => (int) $track->user->id,
                'name' => $track->user->display_name,
                'url' => $track->user->url,
            ],
            'stats' => [
                'views' => (int) $track->view_count,
                'plays' => (int) $track->play_count,
                'downloads' => (int) $track->download_count,
                'comments' => (int) $track->comment_count,
                'favourites' => (int) $track->favourite_count,
            ],
            'url' => $track->url,
            'slug' => $track->slug,
            'is_vocal' => $track->is_vocal,
            'is_explicit' => $track->is_explicit,
            'is_downloadable' => $track->is_downloadable,
            'is_published' => $track->isPublished(),
            'published_at' => $track->isPublished() ? $track->published_at->format('c') : null,
            'duration' => $track->duration,
            'genre' => $track->genre != null
                ?
                [
                    'id' => (int) $track->genre->id,
                    'slug' => $track->genre->slug,
                    'name' => $track->genre->name,
                    'url'  => $track->genre->url,
                ] : null,
            'track_type_id' => $track->track_type_id,
            'covers' => [
                'thumbnail' => $track->getCoverUrl(Image::THUMBNAIL),
                'small' => $track->getCoverUrl(Image::SMALL),
                'normal' => $track->getCoverUrl(Image::NORMAL),
                'original' => $track->getCoverUrl(Image::ORIGINAL),
            ],
            'streams' => [
                'mp3' => $track->getStreamUrl('MP3'),
                'aac' => (! Config::get('app.debug') || is_file($track->getFileFor('AAC'))) ? $track->getStreamUrl('AAC') : null,
                'ogg' => (Config::get('app.debug') || is_file($track->getFileFor('OGG Vorbis'))) ? $track->getStreamUrl('OGG Vorbis') : null,
            ],
            'user_data' => $userData,
            'permissions' => [
                'delete' => Gate::allows('delete', $track),
                'edit' => Gate::allows('edit', $track),
            ],
        ];

        if ($track->album_id != null) {
            $data['album'] = [
                'title' => $track->album->title,
                'url' => $track->album->url,
            ];
        }

        return $data;
    }

    /**
     * Brings together the data about a track used by the track editor.
     * The data structure this returns is what the web API spits out.
     *
     * @param Track $track
     * @return array
     */
    public static function mapPrivateTrackShow(self $track)
    {
        $showSongs = [];
        foreach ($track->showSongs as $showSong) {
            $showSongs[] = ['id' => $showSong->id, 'title' => $showSong->title];
        }

        $returnValue = self::mapPrivateTrackSummary($track);
        $returnValue['album_id'] = $track->album_id;
        $returnValue['show_songs'] = $showSongs;
        $returnValue['cover_id'] = $track->cover_id;
        $returnValue['real_cover_url'] = $track->getCoverUrl(Image::NORMAL);
        $returnValue['cover_url'] = $track->hasCover() ? $track->getCoverUrl(Image::NORMAL) : null;
        $returnValue['released_at'] = $track->released_at ? $track->released_at->toDateString() : null;
        $returnValue['lyrics'] = $track->lyrics;
        $returnValue['description'] = $track->description;
        $returnValue['is_downloadable'] = ! $track->isPublished() ? true : (bool) $track->is_downloadable;
        $returnValue['license_id'] = $track->license_id != null ? $track->license_id : 3;
        $returnValue['username'] = User::whereId($track->user_id)->first()->username;

        // Seasonal
        if (Playlist::where('user_id', 22549)->first()) {
            $returnValue['hwc_submit'] = Playlist::where('user_id', 22549)->first()->tracks()->get()->contains($track);
        }

        return $returnValue;
    }

    /**
     * Brings together the data shown for a "track tile" in a user's account
     * area (where they'd manage unlisted and unpublished tracks and the admin
     * area.
     *
     * The data structure this returns is what the web API spits out.
     * @param Track $track
     * @return array
     */
    public static function mapPrivateTrackSummary(self $track)
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
            'published_at' => $track->published_at ? $track->published_at->format('c') : null,
            'duration' => $track->duration,
            'genre_id' => $track->genre_id,
            'track_type_id' => $track->track_type_id,
            'cover_url' => $track->getCoverUrl(Image::SMALL),
            'is_listed' => (bool) $track->is_listed,
        ];
    }

    protected $table = 'tracks';

    public function genre()
    {
        return $this->belongsTo(Genre::class);
    }

    public function trackType()
    {
        return $this->belongsTo(TrackType::class, 'track_type_id');
    }

    public function comments():HasMany
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
    }

    public function favourites():HasMany
    {
        return $this->hasMany(Favourite::class);
    }

    public function cover()
    {
        return $this->belongsTo(Image::class);
    }

    public function showSongs()
    {
        return $this->belongsToMany(ShowSong::class);
    }

    public function users()
    {
        return $this->hasMany(ResourceUser::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    public function trackFiles()
    {
        return $this->hasMany(TrackFile::class)->where('version', $this->current_version);
    }

    public function trackFilesForAllVersions()
    {
        return $this->hasMany(TrackFile::class);
    }

    public function trackFilesForVersion(int $version)
    {
        return $this->trackFilesForAllVersions()->where('track_files.version', $version);
    }

    public function notifications()
    {
        return $this->morphMany(Activity::class, 'notification_type');
    }

    public function activities():MorphMany
    {
        return $this->morphMany(Activity::class, 'resource');
    }

    public function getYearAttribute()
    {
        return date('Y', strtotime($this->getReleaseDate()));
    }

    public function setTitleAttribute($value)
    {
        $this->setTitleAttributeSlug($value);
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

    /**
     * Returns true if the given user can view this track.
     *
     * @param $user
     * @return bool
     */
    public function canView($user)
    {
        if ($this->isPublished() || $user->hasRole('admin')) {
            return true;
        }

        return $this->user_id == $user->id;
    }

    /**
     * Magic method to create a "url" attribute on Track objects that contains
     * the track's canonical URL.
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        return action('TracksController@getTrack', ['id' => $this->id, 'slug' => $this->slug]);
    }

    /**
     * Magic method to create a "downloadDirectory" attribute on Track objects
     * that contains the directory path it should show up in inside downloaded
     * TrackCollection zip files.
     *
     * @return string
     */
    public function getDownloadDirectoryAttribute()
    {
        if ($this->album) {
            return $this->user->display_name.'/'.$this->album->title;
        }

        return $this->user->display_name;
    }

    /**
     * Returns a track's release date, heuristically generating a plausible one
     * if it is unknown.
     *
     * @return \Carbon\Carbon|string
     */
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

    /**
     * Ensures that the numbered directory this track's files would be stored in
     * server-side exists, creating it if needed.
     */
    public function ensureDirectoryExists()
    {
        $destination = $this->getDirectory();
        umask(0);

        if (! is_dir($destination)) {
            mkdir($destination, 0777, true);
        }
    }

    /**
     * @return bool True if the track has an associated cover file.
     */
    public function hasCover() : bool
    {
        return $this->cover_id != null;
    }

    /**
     * @return bool True if the track has been published and not deleted.
     */
    public function isPublished() : bool
    {
        return $this->published_at != null && $this->deleted_at == null;
    }

    /**
     * Returns the TrackFile object that represents the highest-fidelity version
     * of this track, which is used as the source file for transcoding to other
     * formats.
     *
     * @return TrackFile
     */
    protected function getMasterTrackFile() : TrackFile
    {
        return $this->trackFiles()->where('is_master', true)->first();
    }

    /**
     * The key in `$Formats` above for the master file's format.
     *
     * @return string
     */
    public function getMasterFormatName() : string
    {
        return $this->getMasterTrackFile()->format;
    }

    /**
     * @return bool True if this track's master file is a lossy one.
     */
    public function isMasterLossy() : bool
    {
        return $this->getMasterTrackFile()->isLossy();
    }

    /**
     * Returns the URL to this track's cover art in the given size.
     *
     * @param int $type one of the image size constants in the `Image` class
     * @return string
     */
    public function getCoverUrl($type = Image::NORMAL)
    {
        if (! $this->hasCover()) {
            if ($this->album_id != null) {
                return $this->album->getCoverUrl($type);
            }

            return $this->user->getAvatarUrl($type);
        }

        return $this->cover->getUrl($type);
    }

    /**
     * Returns the "stream" URL for this track in the given format. This is to
     * be used by the on-site player.
     *
     * @param string $format one of the format keys from the `$Formats` array
     * @param string $apiClientId if a URL is being requested for the third-party
     *                            API, this should be set to the requesting app's
     *                            client ID.
     * @return string
     */
    public function getStreamUrl(string $format = 'MP3', string $apiClientId = null)
    {
        return action('TracksController@getStream',
            [
                'id' => $this->id,
                'extension' => self::$Formats[$format]['extension'],
            ] + ($apiClientId !== null ? ['api_client_id' => $apiClientId] : [])
        );
    }

    /**
     * Returns the absolute path to the server-side directory that this track's
     * audio files are stored in.
     *
     * @return string
     */
    public function getDirectory()
    {
        $dir = (string) (floor($this->id / 100) * 100);

        return \Config::get('ponyfm.files_directory').'/tracks/'.$dir;
    }

    public function getDates()
    {
        return ['created_at', 'deleted_at', 'published_at', 'released_at'];
    }

    /**
     * Returns the server-side filename (not path) that a current TrackFile in
     * the given format would have.
     *
     * @param string $format one of the format keys from the `$Formats` array
     * @return string a filename
     * @throws Exception
     */
    public function getFilenameFor(string $format) : string
    {
        if (! isset(self::$Formats[$format])) {
            throw new Exception("$format is not a valid format!");
        }

        $format = self::$Formats[$format];

        return "{$this->id}-v{$this->current_version}.{$format['extension']}";
    }

    /**
     * Returns the filename that this track would have in the given format when
     * downloaded by a user.
     *
     * @param string $format one of the format keys from the `$Formats` array
     * @return string
     * @throws Exception
     */
    public function getDownloadFilenameFor(string $format) : string
    {
        if (! isset(self::$Formats[$format])) {
            throw new Exception("$format is not a valid format!");
        }

        $format = self::$Formats[$format];

        return "{$this->title}.{$format['extension']}";
    }

    /**
     * Returns the absolute path to this track's file in a given format.
     *
     * @param string $format one of the format keys from the `$Formats` array
     * @return string absolute path to a track file
     * @throws Exception
     */
    public function getFileFor(string $format) : string
    {
        if (! isset(self::$Formats[$format])) {
            throw new Exception("$format is not a valid format!");
        }

        $format = self::$Formats[$format];

        return "{$this->getDirectory()}/{$this->id}-v{$this->current_version}.{$format['extension']}";
    }

    /**
     * Returns the path to the "temporary" master file uploaded by the user.
     * This file is used during the upload process to generate the actual master
     * file stored by Pony.fm.
     *
     * @param int $version
     * @return string
     */
    public function getTemporarySourceFileForVersion(int $version):string
    {
        return Config::get('ponyfm.files_directory').'/queued-tracks/'.$this->id.'v'.$version;
    }

    /**
     * Returns the URL to download a track file from in the given format.
     *
     * @param string $format one of the format keys from the `$Formats` array
     * @return string
     * @throws Exception
     */
    public function getUrlFor(string $format) : string
    {
        if (! isset(self::$Formats[$format])) {
            throw new Exception("$format is not a valid format!");
        }

        $format = self::$Formats[$format];

        return action('TracksController@getDownload', ['id' => $this->id, 'extension' => $format['extension']]);
    }

    /**
     * @return int one of the Track::STATUS_* values, indicating whether this track is currently being processed
     */
    public function getStatusAttribute():int
    {
        return $this->trackFiles->reduce(function ($carry, $trackFile) {
            if ((int) $trackFile->status === TrackFile::STATUS_PROCESSING_ERROR) {
                return static::STATUS_ERROR;
            } elseif ($carry !== static::STATUS_ERROR &&
                in_array($trackFile->status, [TrackFile::STATUS_PROCESSING, TrackFile::STATUS_PROCESSING_PENDING])) {
                return static::STATUS_PROCESSING;
            } elseif (! in_array($carry, [static::STATUS_ERROR, static::STATUS_PROCESSING, TrackFile::STATUS_PROCESSING_PENDING]) &&
                (int) $trackFile->status === TrackFile::STATUS_NOT_BEING_PROCESSED
            ) {
                return static::STATUS_COMPLETE;
            } else {
                return $carry;
            }
        }, static::STATUS_COMPLETE);
    }

    /**
     * Updates this track's hash of the artist name and track title.
     */
    public function updateHash()
    {
        $this->hash = md5(Helpers::sanitizeInputForHashing($this->user->display_name).' - '.Helpers::sanitizeInputForHashing($this->title));
    }

    /**
     * Ensures that the metadata for this track's file in the given format is
     * up to date.
     * @param string $trackFileFormat one of the format keys from the `$Formats` array OR
     *                                'all' to indicate the operation should be performed on all formats
     */
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

    /**
     * Runs the command to update the given TrackFile's metadata.
     *
     * @param TrackFile $trackFile
     */
    private function updateTagsForTrackFile(TrackFile $trackFile)
    {
        $trackFile->touch();

        if (\File::exists($trackFile->getFile())) {
            $format = $trackFile->format;
            $data = self::$Formats[$format];

            $this->{$data['tag_method']}($format);
        }
    }

    /**
     * Writes a TrackFile's tags using AtomicParsley. This is useful for MP4
     * files (AAC and ALAC). This function is called from updateTagsForTrackFile.
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     * @param string $format one of the format keys from the `$Formats` array
     */
    private function updateTagsWithAtomicParsley(string $format)
    {
        $command = 'AtomicParsley "'.$this->getFileFor($format).'" ';
        $command .= '--title '.escapeshellarg($this->title).' ';
        $command .= '--artist '.escapeshellarg($this->user->display_name).' ';
        $command .= '--year "'.$this->year.'" ';
        $command .= '--genre '.escapeshellarg($this->genre != null ? $this->genre->name : '').' ';
        $command .= '--copyright '.escapeshellarg('© '.$this->year.' '.$this->user->display_name).' ';
        $command .= '--comment "'.'Downloaded from: https://pony.fm/'.'" ';
        $command .= '--encodingTool "'.'Pony.fm - https://pony.fm/'.'" ';

        if ($this->album_id !== null) {
            $command .= '--album '.escapeshellarg($this->album->title).' ';
            $command .= '--tracknum '.$this->track_number.' ';
        }

        if ($this->cover !== null) {
            $command .= '--artwork '.$this->cover->getFile(Image::ORIGINAL).' ';
        }

        $command .= '--overWrite';

        External::execute($command);
    }

    /**
     * Writes a TrackFile's tags using getId3(). This is useful for MP3, FLAC,
     * and OGG Vorbis files. This function is called from updateTagsForTrackFile.
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     * @param string $format one of the format keys from the `$Formats` array
     */
    private function updateTagsWithGetId3(string $format)
    {
        require_once app_path().'/Library/getid3/getid3/getid3.php';
        require_once app_path().'/Library/getid3/getid3/write.php';
        $tagWriter = new getid3_writetags;

        $tagWriter->overwrite_tags = true;
        $tagWriter->tag_encoding = 'UTF-8';
        $tagWriter->remove_other_tags = true;

        $tagWriter->tag_data = [
            'title' => [$this->title],
            'artist' => [$this->user->display_name],
            'year' => [''.$this->year],
            'genre' => [$this->genre != null ? $this->genre->name : ''],
            'comment' => ['Downloaded from: https://pony.fm/'],
            'copyright' => ['© '.$this->year.' '.$this->user->display_name],
            'publisher' => ['Pony.fm - https://pony.fm/'],
            'encoded_by' => ['https://pony.fm/'],
//                'url_artist'            => [$this->user->url],
//                'url_source'            => [$this->url],
//                'url_file'                => [$this->url],
            'url_publisher' => ['https://pony.fm/'],
        ];

        if ($this->album_id !== null) {
            $tagWriter->tag_data['album'] = [$this->album->title];
            $tagWriter->tag_data['track'] = [$this->track_number];
        }

        if ($format == 'MP3' && $this->cover_id != null && is_file($this->cover->getFile())) {
            $tagWriter->tag_data['attached_picture'][0] = [
                'data' => file_get_contents($this->cover->getFile(Image::ORIGINAL)),
                'picturetypeid' => 2,
                'description' => 'cover',
                'mime' => $this->cover->mime,
            ];
        }

        $tagWriter->filename = $this->getFileFor($format);
        $tagWriter->tagformats = [self::$Formats[$format]['tag_format']];

        if ($tagWriter->WriteTags()) {
            if (! empty($tagWriter->warnings)) {
                Log::warning('Track #'.$this->id.': There were some warnings:<br />'.implode(
                    '<br /><br />',
                    $tagWriter->warnings
                ));
            }
        } else {
            Log::error('Track #'.$this->id.': Failed to write tags!<br />'.implode(
                '<br /><br />',
                $tagWriter->errors
            ));
        }
    }

    private function getCacheKey($key) : string
    {
        return 'track-'.$this->id.'-'.$key;
    }

    /**
     * Marks this track and associated notification records as deleted.
     */
    public function delete()
    {
        DB::transaction(function () {
            $this->activities()->delete();
            parent::delete();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function shouldBeIndexed():bool
    {
        return $this->is_listed &&
               $this->published_at !== null &&
               ! $this->trashed();
    }

    /**
     * {@inheritdoc}
     */
    public function toElasticsearch():array
    {
        return [
            'title'         => $this->title,
            'artist'        => $this->user->display_name,
            'published_at'  => $this->published_at ? $this->published_at->toIso8601String() : null,
            'genre'         => $this->genre->name,
            'track_type'    => $this->trackType->title,
            'show_songs'    => $this->showSongs->pluck('title'),
        ];
    }

    /**
     * Returns the word used to identify this "type" of content in notifications.
     * @return string
     */
    public function getResourceType():string
    {
        return 'track';
    }

    /**
     * Returns the next version after this track's current one.
     *
     * @return int
     */
    public function getNextVersion() : int
    {
        return $this->current_version + 1;
    }
}
