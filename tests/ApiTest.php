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

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Poniverse\Ponyfm\Track;
use Poniverse\Ponyfm\User;

class ApiTest extends TestCase {
    use DatabaseMigrations;
    use WithoutMiddleware;

    public function testUploadWithoutFile() {
        $user = factory(User::class)->create();

        $this->actingAs($user)
             ->post('/api/v1/tracks', [])
             ->seeJsonEquals([
                 'errors' => [
                     'track' => ['You must upload an audio file!']
                 ],
                 'message' => 'Validation failed'
             ]);
        $this->assertResponseStatus(400);
    }

    public function testUploadWithFileWithoutAutoPublish() {
        $this->callUploadWithParameters([
            'auto_publish' => false
        ]);

        $this->seeJsonEquals([
                'message'       => "This track has been accepted for processing! Poll the status_url to know when it's ready to publish. It will be published at the track_url.",
                'id'            => "1",
                'status_url'    => "http://ponyfm-testing.poni/api/v1/tracks/1/upload-status",
                'track_url'     => "http://ponyfm-testing.poni/tracks/1-ponyfm-test",
            ]);
    }

    public function testUploadWithFileWithAutoPublish() {
        $this->callUploadWithParameters([]);

        $this->seeJsonEquals([
                'message'       => "This track has been accepted for processing! Poll the status_url to know when it has been published. It will be published at the track_url.",
                'id'            => "1",
                'status_url'    => "http://ponyfm-testing.poni/api/v1/tracks/1/upload-status",
                'track_url'     => "http://ponyfm-testing.poni/tracks/1-ponyfm-test",
            ]);

        $this->visit('/tracks/1-ponyfm-test');
        $this->assertResponseStatus(200);
    }

    public function testUploadWithOptionalData() {
        $track = factory(Track::class)->make();

        $this->callUploadWithParameters([
            'title'             => $track->title,
            'track_type_id'     => $track->track_type_id,
            'genre'             => $track->genre,
            'album'             => $track->album,
            'released_at'       => \Carbon\Carbon::create(2015, 1, 1, 1, 1, 1)->toIso8601String(),
            'description'       => $track->description,
            'lyrics'            => $track->lyrics,
            'is_vocal'          => true,
            'is_explicit'       => true,
            'is_downloadable'   => false,
            'is_listed'         => false,
            'metadata'          => $track->metadata
        ]);

        $this->seeInDatabase('genres', [
            'name' => $track->genre
        ]);

        $this->seeInDatabase('albums', [
            'title' => $track->album
        ]);

        $this->seeInDatabase('tracks', [
            'title'             => $track->title,
            'track_type_id'     => $track->track_type_id,
            'released_at'       => "2015-01-01 01:01:01",
            'description'       => $track->description,
            'lyrics'            => $track->lyrics,
            'is_vocal'          => true,
            'is_explicit'       => true,
            'is_downloadable'   => false,
            'is_listed'         => false,
            'metadata'          => $track->metadata
        ]);
    }
}
