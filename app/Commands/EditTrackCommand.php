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

namespace App\Commands;

use App\Models\Album;
use App\Models\Image;
use App\Models\Playlist;
use App\Models\Track;
use App\Models\TrackType;
use App\Models\User;
use DB;
use Gate;
use Notification;

class EditTrackCommand extends CommandBase
{
    private $_trackId;

    /**
     * @var Track
     */
    private $_track;
    private $_input;

    public function __construct($trackId, $input)
    {
        $this->_trackId = $trackId;
        $this->_track = Track::find($trackId);
        $this->_input = $input;
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        return $this->_track && Gate::allows('edit', $this->_track);
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $isVocal = (isset($this->_input['is_vocal']) && $this->_input['is_vocal'] == 'true') ? true : false;

        $rules = [
            'title' => 'required|min:3|max:80',
            'released_at' => 'before:'.
                    (date('Y-m-d', time() + (86400 * 2))).(
                    isset($this->_input['released_at']) && $this->_input['released_at'] != ''
                 ? '|date'
                 : ''),
            'license_id' => 'required|exists:licenses,id',
            'genre_id' => 'required|exists:genres,id',
            'cover' => 'image|mimes:png,jpeg|min_width:350|min_height:350',
            'track_type_id' => 'required|exists:track_types,id|not_in:'.TrackType::UNCLASSIFIED_TRACK,
            'cover_id' => 'exists:images,id',
            'album_id' => 'exists:albums,id',
            'username' => 'exists:users,username',
        ];

        if (isset($this->_input['track_type_id']) && $this->_input['track_type_id'] == TrackType::OFFICIAL_TRACK_REMIX) {
            $rules['show_song_ids'] = 'required|exists:show_songs,id';
            $this->_input['show_song_ids'] = json_decode($this->_input['show_song_ids']);
        }

        $validator = \Validator::make($this->_input, $rules);

        if ($validator->fails()) {
            return CommandResponse::fail($validator);
        }

        $track = $this->_track;
        $track->title = $this->_input['title'];
        $track->released_at = isset($this->_input['released_at']) && $this->_input['released_at'] != '' ? strtotime($this->_input['released_at']) : null;
        $track->description = isset($this->_input['description']) ? $this->_input['description'] : '';
        $track->lyrics = isset($this->_input['lyrics']) ? $this->_input['lyrics'] : '';
        $track->license_id = $this->_input['license_id'];
        $track->genre_id = $this->_input['genre_id'];
        $track->track_type_id = $this->_input['track_type_id'];
        $track->is_explicit = $this->_input['is_explicit'] == 'true';
        $track->is_downloadable = $this->_input['is_downloadable'] == 'true';
        $track->is_listed = $this->_input['is_listed'] == 'true';
        $track->is_vocal = $isVocal;

        if (isset($this->_input['album_id']) && strlen(trim($this->_input['album_id']))) {
            if ($track->album_id != null && $track->album_id != $this->_input['album_id']) {
                $this->removeTrackFromAlbum($track);
            }

            if ($track->album_id != $this->_input['album_id']) {
                $this->addTrackToAlbum($track, $this->_input['album_id']);
            }
        } else {
            if ($track->album_id != null) {
                $this->removeTrackFromAlbum($track);
            }

            $track->track_number = null;
            $track->album_id = null;
        }

        if ($track->track_type_id == TrackType::OFFICIAL_TRACK_REMIX) {
            $track->showSongs()->sync($this->_input['show_song_ids']);
        } else {
            $track->showSongs()->sync([]);
        }

        if ($track->published_at == null) {
            $track->published_at = new \DateTime();

            DB::table('tracks')->whereUserId($track->user_id)->update(['is_latest' => false]);
            $track->is_latest = true;

            Notification::publishedNewTrack($track);
        }

        if (isset($this->_input['cover_id'])) {
            $track->cover_id = $this->_input['cover_id'];
        } else {
            if (isset($this->_input['cover'])) {
                $cover = $this->_input['cover'];
                $track->cover_id = Image::upload($cover, $track->user_id)->id;
            } else {
                if ($this->_input['remove_cover'] == 'true') {
                    $track->cover_id = null;
                }
            }
        }

        $oldid = null;

        if (isset($this->_input['username'])) {
            $newid = User::where('username', $this->_input['username'])->first()->id;

            if ($track->user_id != $newid) {
                $oldid = $track->user_id;
                $track->user_id = $newid;
            }
        }

        $track->updateTags();
        $track->save();

        User::whereId($this->_track->user_id)->update([
            'track_count' => DB::raw('(SELECT COUNT(id) FROM tracks WHERE deleted_at IS NULL AND published_at IS NOT NULL AND user_id = '.$this->_track->user_id.')'),
        ]);

        if ($oldid != null) {
            User::whereId($oldid)->update([
                'track_count' => DB::raw('(SELECT COUNT(id) FROM tracks WHERE deleted_at IS NULL AND published_at IS NOT NULL AND user_id = '.$oldid.')'),
            ]);
        }

        if (isset($this->_input['hwc_submit']) && new \DateTime() < new \DateTime('2016-12-20 23:59:59')) {
            $playlist = Playlist::where('user_id', 22549)->first();

            if ($this->_input['hwc_submit'] == 'true') {
                if (! $playlist->tracks()->get()->contains($track)) {
                    $songIndex = $playlist->trackCount() + 1;
                    $playlist->tracks()->attach($track, ['position' => $songIndex]);
                    $playlist->touch();

                    Playlist::where('id', $playlist->id)->update([
                        'track_count' => DB::raw('(SELECT COUNT(id) FROM playlist_track WHERE playlist_id = '.$playlist->id.')'),
                    ]);
                }
            } else {
                if ($playlist->tracks()->get()->contains($track)) {
                    $playlist->tracks()->detach($track);

                    Playlist::whereId($playlist->id)->update([
                        'track_count' => DB::raw('(SELECT COUNT(id) FROM playlist_track WHERE playlist_id = '.$playlist->id.')'),
                    ]);
                }
            }
        }

        return CommandResponse::succeed(['real_cover_url' => $track->getCoverUrl(Image::NORMAL)]);
    }

    private function removeTrackFromAlbum(Track $track)
    {
        $album = $track->album;
        $index = 0;

        foreach ($album->tracks as $track) {
            /** @var $track Track */
            if ($track->id == $this->_trackId) {
                continue;
            }

            $track->track_number = ++$index;
            $track->updateTags();
            $track->save();
        }

        Album::whereId($album->id)->update([
            'track_count' => DB::table('tracks')->where('album_id', '=', $album->id)->count(),
        ]);
    }

    private function addTrackToAlbum(Track $track, $album_id)
    {
        $album = Album::whereId($album_id)->first();

        $count = $album->track_count + 1;
        $track->track_number = $count;
        $track->album_id = $album->id;
        $track->updateTags();
        $track->save();

        $album->update([
            'track_count' => DB::table('tracks')->where('album_id', '=', $album->id)->count(),
        ]);
    }
}
