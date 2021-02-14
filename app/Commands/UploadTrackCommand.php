<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0.
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

namespace Poniverse\Ponyfm\Commands;

use Auth;
use Carbon\Carbon;
use Config;
use Gate;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\Request;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\User;
use Validator;

class UploadTrackCommand extends CommandBase
{
    use DispatchesJobs;

    private $_artist;
    private $_allowLossy;
    private $_allowShortTrack;
    private $_customTrackSource;
    private $_autoPublishByDefault;
    private $_track;
    private $_version;
    private $_isReplacingTrack;

    public $_file;

    /**
     * @return bool
     */
    public function authorize()
    {
        if ($this->_isReplacingTrack) {
            return $this->_track && Gate::allows('edit', $this->_track);
        } else {
            return Gate::allows('create-track', $this->_artist);
        }
    }

    /**
     * UploadTrackCommand constructor.
     *
     * @param bool $allowLossy
     * @param bool $allowShortTrack allow tracks shorter than 30 seconds
     * @param string|null $customTrackSource value to set in the track's "source" field; if left blank, "direct_upload" is used
     * @param bool $autoPublishByDefault
     * @param int $version
     * @param Track $track | null
     */
    public function __construct(
        bool $allowLossy = false,
        bool $allowShortTrack = false,
        string $customTrackSource = null,
        bool $autoPublishByDefault = false,
        int $version = 1,
        $track = null
    ) {
        $userSlug = Request::get('user_slug', null);
        $this->_artist =
            $userSlug !== null
            ? User::where('slug', $userSlug)->first()
            : Auth::user();

        $this->_allowLossy = $allowLossy;
        $this->_allowShortTrack = $allowShortTrack;
        $this->_customTrackSource = $customTrackSource;
        $this->_autoPublishByDefault = $autoPublishByDefault;
        $this->_version = $version;
        $this->_track = $track;
        $this->_isReplacingTrack = $this->_track !== null && $version > 1;
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $trackFile = null;
        $source = 'direct_upload';

        if ($this->_file !== null) {
            $trackFile = $this->_file;
            $source = 'eqbeats';
        } else {
            $trackFile = Request::file('track', null);
        }

        if (! $this->_isReplacingTrack) {
            $coverFile = Request::file('cover', null);
        }

        if (null === $trackFile) {
            if ($this->_isReplacingTrack) {
                $this->_track->version_upload_status = Track::STATUS_ERROR;
                $this->_track->update();
            }

            return CommandResponse::fail(['track' => ['You must upload an audio file!']]);
        }

        $audio = \AudioCache::get($trackFile->getPathname());

        if (! $this->_isReplacingTrack) {
            $this->_track = new Track();
            $this->_track->user_id = $this->_artist->id;
            // The title set here is a placeholder; it'll be replaced by ParseTrackTagsCommand
            // if the file contains a title tag.
            $this->_track->title = mb_strimwidth(Request::get('title', pathinfo($trackFile->getClientOriginalName(), PATHINFO_FILENAME)), 0, 100, '...');
            // The duration/version of the track cannot be changed until the encoding is successful
            $this->_track->duration = $audio->getDuration();
            $this->_track->current_version = $this->_version;
            $this->_track->save();
        }
        $this->_track->ensureDirectoryExists();

        if (! is_dir(Config::get('ponyfm.files_directory').'/tmp')) {
            mkdir(Config::get('ponyfm.files_directory').'/tmp', 0755, true);
        }

        if (! is_dir(Config::get('ponyfm.files_directory').'/queued-tracks')) {
            mkdir(Config::get('ponyfm.files_directory').'/queued-tracks', 0755, true);
        }

        $trackFile = $trackFile->move(Config::get('ponyfm.files_directory').'/queued-tracks', $this->_track->id.'v'.$this->_version);

        $input = Request::all();
        $input['track'] = $trackFile;

        // Prevent the setting of the cover index for validation
        if (! $this->_isReplacingTrack && isset($coverFile)) {
            $input['cover'] = $coverFile;
        }

        $rules = [
            'track' => 'required|'
                .($this->_allowLossy
                    ? 'audio_format:flac,alac,pcm,adpcm,aac,mp3,vorbis|'
                    : 'audio_format:flac,alac,pcm,adpcm|')
                .($this->_allowShortTrack ? '' : 'min_duration:30|')
                .'audio_channels:1,2',
        ];
        if (! $this->_isReplacingTrack) {
            array_merge($rules, [
                'cover'             => 'image|mimes:png,jpeg|min_width:350|min_height:350',
                'auto_publish'      => 'boolean',
                'title'             => 'string',
                'track_type_id'     => 'exists:track_types,id',
                'genre'             => 'string',
                'album'             => 'string',
                'track_number'      => 'integer',
                'released_at'       => 'date_format:'.Carbon::ISO8601,
                'description'       => 'string',
                'lyrics'            => 'string',
                'is_vocal'          => 'boolean',
                'is_explicit'       => 'boolean',
                'is_downloadable'   => 'boolean',
                'is_listed'         => 'boolean',
                'metadata'          => 'json',
            ]);
        }
        $validator = \Validator::make($input, $rules);

        if ($validator->fails()) {
            if ($this->_isReplacingTrack) {
                $this->_track->version_upload_status = Track::STATUS_ERROR;
                $this->_track->update();
            } else {
                $this->_track->delete();
            }

            return CommandResponse::fail($validator);
        }

        if (! $this->_isReplacingTrack) {
            // If json_decode() isn't called here, Laravel will surround the JSON
            // string with quotes when storing it in the database, which breaks things.
            $this->_track->metadata = json_decode(Request::get('metadata', null));
        }
        $autoPublish = (bool) ($input['auto_publish'] ?? $this->_autoPublishByDefault);
        $this->_track->source = $this->_customTrackSource ?? $source;
        $this->_track->save();

        // If the cover was null, and not included, add it back in as null so that
        // other commands do not encounter a undefined index.
        if (! isset($input['cover'])) {
            $input['cover'] = null;
        }

        if (! $this->_isReplacingTrack) {
            // Parse any tags in the uploaded files.
            $parseTagsCommand = new ParseTrackTagsCommand($this->_track, $trackFile, $input);
            $result = $parseTagsCommand->execute();
            if ($result->didFail()) {
                if ($this->_isReplacingTrack) {
                    $this->_track->version_upload_status = Track::STATUS_ERROR;
                    $this->_track->update();
                }

                return $result;
            }
        }

        $generateTrackFiles = new GenerateTrackFilesCommand($this->_track, $trackFile, $autoPublish, true, $this->_isReplacingTrack, $this->_version);

        return $generateTrackFiles->execute();
    }
}
