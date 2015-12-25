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

    public function testUploadWithFile() {
        $this->expectsJobs(Poniverse\Ponyfm\Jobs\EncodeTrackFile::class);

        $user = factory(User::class)->create();

        $file = $this->getTestFileForUpload('ponyfm-test.flac');
        $this->actingAs($user)
            ->call('POST', '/api/v1/tracks', [], [], ['track' => $file]);

        $this->assertResponseStatus(202);
        $this->seeJsonEquals([
                'message'       => "This track has been accepted for processing! Poll the status_url to know when it's ready to publish.",
                'id'            => 1,
                'status_url'    => "http://ponyfm-testing.poni/api/v1/tracks/1/upload-status"
            ]);
    }
}
