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

namespace Poniverse\Ponyfm\Http\Controllers;

use Poniverse\Ponyfm\Models\User;
use Auth;
use Config;
use DB;
use Input;
use Poniverse;
use Redirect;

class AuthController extends Controller
{
    protected $poniverse;

    public function __construct()
    {
        $this->poniverse = new Poniverse(Config::get('poniverse.client_id'), Config::get('poniverse.secret'));
        $this->poniverse->setRedirectUri(action('AuthController@getOAuth'));
    }

    public function getLogin()
    {
        if (Auth::guest()) {
            return Redirect::to($this->poniverse->getAuthenticationUrl('login'));
        }

        return Redirect::to('/');
    }

    public function postLogout()
    {
        Auth::logout();

        return Redirect::to('/');
    }

    public function getOAuth()
    {
        $code = $this->poniverse->getClient()->getAccessToken(
            Config::get('poniverse.urls')['token'],
            'authorization_code',
            [
                'code' => Input::query('code'),
                'redirect_uri' => action('AuthController@getOAuth')
            ]
        );

        if ($code['code'] != 200) {
            if ($code['code'] == 400 && $code['result']['error_description'] == 'The authorization code has expired' && !isset($this->request['login_attempt'])) {
                return Redirect::to($this->poniverse->getAuthenticationUrl('login_attempt'));
            }

            return Redirect::to('/')->with(
                'message',
                'Unfortunately we are having problems attempting to log you in at the moment. Please try again at a later time.'
            );
        }

            $this->poniverse->setAccessToken($code['result']['access_token']);
            $poniverseUser = $this->poniverse->getUser();
            $token = DB::table('oauth2_tokens')->where('external_user_id', '=', $poniverseUser['id'])->where(
                'service',
                '=',
                'poniverse'
            )->first();

            $setData = [
            'access_token' => $code['result']['access_token'],
            'expires' => date('Y-m-d H:i:s', strtotime("+".$code['result']['expires_in']." Seconds", time())),
            'type' => $code['result']['token_type'],
            ];

            if (isset($code['result']['refresh_token']) && !empty($code['result']['refresh_token'])) {
                $setData['refresh_token'] = $code['result']['refresh_token'];
            }

            if ($token) {
                //User already exists, update access token and refresh token if provided.
                DB::table('oauth2_tokens')->where('id', '=', $token->id)->update($setData);

                return $this->loginRedirect(User::find($token->user_id));
            }

        // Check by login name to see if they already have an account
            $user = User::findOrCreate($poniverseUser['username'], $poniverseUser['display_name'], $poniverseUser['email']);

            if ($user->wasRecentlyCreated) {
                return $this->loginRedirect($user);
            }

        // We need to insert a new token row :O

            $setData['user_id'] = $user->id;
            $setData['external_user_id'] = $poniverseUser['id'];
            $setData['service'] = 'poniverse';

            DB::table('oauth2_tokens')->insert($setData);

            return $this->loginRedirect($user);
    }

    protected function loginRedirect($user, $rememberMe = true)
    {
        Auth::login($user, $rememberMe);

        return Redirect::to('/');
    }
}
