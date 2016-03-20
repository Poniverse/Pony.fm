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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Poniverse\Ponyfm\Models\User;

class ApiAuthTest extends TestCase {
    use DatabaseMigrations;

    /**
     * Ensures that when we call the Pony.fm API with a user who has never
     * logged into Pony.fm before, a Pony.fm account is created for them using
     * their Poniverse details.
     */
    public function testApiCreatesNewUser() {
        $user = factory(User::class)->make();
        $accessTokenInfo = new \Poniverse\AccessTokenInfo('nonsense-token');
        $accessTokenInfo->setIsActive(true);
        $accessTokenInfo->setScopes(['basic', 'ponyfm:tracks:upload']);

        $poniverse = Mockery::mock('overload:Poniverse');
        $poniverse->shouldReceive('getUser')
            ->andReturn([
                'username' => $user->username,
                'display_name' => $user->display_name,
                'email' => $user->email,
            ]);

        $poniverse->shouldReceive('setAccessToken');

        $poniverse
            ->shouldReceive('getAccessTokenInfo')
            ->andReturn($accessTokenInfo);

        $this->dontSeeInDatabase('users', ['username' => $user->username]);
        $this->post('/api/v1/tracks', ['access_token' => 'nonsense-token']);
        $this->seeInDatabase('users', ['username' => $user->username]);
    }

    public function testApiClientIdIsRecordedWhenUploadingTrack() {
        $user = factory(User::class)->make();
        $accessTokenInfo = new \Poniverse\AccessTokenInfo('nonsense-token');
        $accessTokenInfo->setIsActive(true);
        $accessTokenInfo->setClientId('Unicorns and rainbows');
        $accessTokenInfo->setScopes(['basic', 'ponyfm:tracks:upload']);

        $poniverse = Mockery::mock('overload:Poniverse');
        $poniverse->shouldReceive('getUser')
                  ->andReturn([
                      'username' => $user->username,
                      'display_name' => $user->display_name,
                      'email' => $user->email,
                  ]);

        $poniverse->shouldReceive('setAccessToken');

        $poniverse
            ->shouldReceive('getAccessTokenInfo')
            ->andReturn($accessTokenInfo);

        $this->callUploadWithParameters(['access_token' => $accessTokenInfo->getToken()]);
        $this->assertSessionHas('api_client_id', $accessTokenInfo->getClientId());
        $this->seeInDatabase('tracks', ['source' => $accessTokenInfo->getClientId()]);
    }
}
