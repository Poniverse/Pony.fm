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

namespace Poniverse\Ponyfm\Commands;

use Carbon\Carbon;
use Config;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Input;
use Poniverse\Ponyfm\Models\Track;
use AudioCache;
use Validator;

class UploadTrackCommand extends CommandBase
{
    use DispatchesJobs;

    private $_allowLossy;
    private $_allowShortTrack;
    private $_customTrackSource;
    private $_autoPublishByDefault;

    /**
     * UploadTrackCommand constructor.
     *
     * @param bool $allowLossy
     * @param bool $allowShortTrack allow tracks shorter than 30 seconds
     * @param string|null $customTrackSource value to set in the track's "source" field; if left blank, "direct_upload" is used
     * @param bool $autoPublishByDefault
     */
    public function __construct(bool $allowLossy = false, bool $allowShortTrack = false, string $customTrackSource = null, bool $autoPublishByDefault = false)
    {
        $this->_allowLossy = $allowLossy;
        $this->_allowShortTrack = $allowShortTrack;
        $this->_customTrackSource = $customTrackSource;
        $this->_autoPublishByDefault = $autoPublishByDefault;
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        return \Auth::user() != null;
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $user = \Auth::user();
        $trackFile = Input::file('track', null);
        $coverFile = Input::file('cover', null);

        if (null === $trackFile) {
            return CommandResponse::fail(['track' => ['You must upload an audio file!']]);
        }

        $audio = \AudioCache::get($trackFile->getPathname());

        $track = new Track();
        $track->user_id = $user->id;
        // The title set here is a placeholder; it'll be replaced by ParseTrackTagsCommand
        // if the file contains a title tag.
        $track->title = Input::get('title', pathinfo($trackFile->getClientOriginalName(), PATHINFO_FILENAME));
        $track->duration = $audio->getDuration();
        $track->save();
        $track->ensureDirectoryExists();

        if (!is_dir(Config::get('ponyfm.files_directory').'/tmp')) {
            mkdir(Config::get('ponyfm.files_directory').'/tmp', 0755, true);
        }

        if (!is_dir(Config::get('ponyfm.files_directory').'/queued-tracks')) {
            mkdir(Config::get('ponyfm.files_directory').'/queued-tracks', 0755, true);
        }
        $trackFile = $trackFile->move(Config::get('ponyfm.files_directory').'/queued-tracks', $track->id);

        $input = Input::all();
        $input['track'] = $trackFile;
        $input['cover'] = $coverFile;

        $validator = \Validator::make($input, [
            'track' =>
                'required|'
                . ($this->_allowLossy
                    ? 'audio_format:flac,alac,pcm,adpcm,aac,mp3,vorbis|'
                    : 'audio_format:flac,alac,pcm,adpcm|')
                . ($this->_allowShortTrack ? '' : 'min_duration:30|')
                . 'audio_channels:1,2',

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
            'cover'             => 'image|mimes:png,jpeg|min_width:350|min_height:350',
            'metadata'          => 'json',
        ]);

        if ($validator->fails()) {
            $track->delete();
            return CommandResponse::fail($validator);
        }
        $autoPublish = (bool) ($input['auto_publish'] ?? $this->_autoPublishByDefault);
        $track->source = $this->_customTrackSource ?? 'direct_upload';

        // If json_decode() isn't called here, Laravel will surround the JSON
        // string with quotes when storing it in the database, which breaks things.
        $track->metadata = json_decode(Input::get('metadata', null));
        $track->save();

        // Parse any tags in the uploaded files.
        $parseTagsCommand = new ParseTrackTagsCommand($track, $trackFile, $input);
        $result = $parseTagsCommand->execute();
        if ($result->didFail()) {
            return $result;
        }

        $generateTrackFiles = new GenerateTrackFilesCommand($track, $trackFile, $autoPublish);
        return $generateTrackFiles->execute();
    }
}
