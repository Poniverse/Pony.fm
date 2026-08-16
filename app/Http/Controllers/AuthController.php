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

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;

class AuthController extends Controller
{
    public function getLogin(Request $request)
    {
        if (Auth::guest()) {
            $provider = $this->oauthProvider();
            $authorizationUrl = $provider->getAuthorizationUrl();

            $request->session()->put('oauth2_state', $provider->getState());
            $request->session()->put('oauth2_pkce_code', $provider->getPkceCode());
            $request->session()->put('oauth2_popup', $request->boolean('popup'));

            return redirect($authorizationUrl);
        }

        if ($request->boolean('popup')) {
            return response()->view('auth.popup', ['success' => true]);
        }

        return redirect()->to('/');
    }

    public function postLogout()
    {
        Auth::logout();

        return redirect()->to('/');
    }

    public function getOAuth(Request $request)
    {
        $expectedState = $request->session()->pull('oauth2_state');
        $pkceCode = $request->session()->pull('oauth2_pkce_code');

        if (
            ! $request->filled('code') ||
            ! $expectedState ||
            $request->query('state') !== $expectedState
        ) {
            return $this->loginFailedRedirect();
        }

        $provider = $this->oauthProvider();
        $provider->setPkceCode($pkceCode);

        try {
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $request->query('code'),
            ]);
            $claims = $provider->getResourceOwner($accessToken)->toArray();
        } catch (IdentityProviderException $e) {
            Log::error($e);

            return $this->loginFailedRedirect();
        }

        $poniverseId = (int) ($claims['sub'] ?? 0);
        $username = $claims['preferred_username'] ?? null;
        $displayName = $claims['name'] ?? $username;
        $email = $claims['email'] ?? null;

        if (! $poniverseId || ! $username) {
            Log::error('Poniverse userinfo response was missing the sub or preferred_username claim.', ['claims' => array_keys($claims)]);

            return $this->loginFailedRedirect();
        }

        $token = DB::table('oauth2_tokens')
            ->where('external_user_id', '=', $poniverseId)
            ->where('service', '=', 'poniverse')
            ->first();

        if ($token) {
            return $this->loginRedirect(User::find($token->user_id));
        }

        // Check by login name to see if they already have an account
        $user = User::findOrCreate($username, $displayName, $email);

        if ($user->wasRecentlyCreated) {
            // Record the Poniverse account link. Poniverse is used purely as
            // an identity provider, so no tokens are kept around - the row
            // exists only to map the Poniverse account ID to the local user.
            DB::table('oauth2_tokens')->insert([
                'user_id' => $user->id,
                'external_user_id' => $poniverseId,
                'service' => 'poniverse',
                'type' => 'Bearer',
                'access_token' => '',
                'refresh_token' => '',
                'expires' => now(),
            ]);

            // Subscribe the user to default email notifications
            foreach (Activity::DEFAULT_EMAIL_TYPES as $activityType) {
                $user->emailSubscriptions()->create(['activity_type' => $activityType]);
            }
        }

        return $this->loginRedirect($user);
    }

    protected function loginRedirect($user, $rememberMe = true)
    {
        Auth::login($user, $rememberMe);

        if (request()->session()->pull('oauth2_popup')) {
            return response()->view('auth.popup', ['success' => true]);
        }

        return redirect()->to('/');
    }

    protected function loginFailedRedirect()
    {
        if (request()->session()->pull('oauth2_popup')) {
            return response()->view('auth.popup', ['success' => false]);
        }

        return redirect()->to('/')->with(
            'message',
            'Unfortunately we are having problems attempting to log you in at the moment. Please try again at a later time.'
        );
    }

    protected function oauthProvider(): GenericProvider
    {
        return new GenericProvider([
            'clientId' => config('poniverse.client_id'),
            'clientSecret' => config('poniverse.secret'),
            'redirectUri' => action([static::class, 'getOAuth']),
            'urlAuthorize' => config('poniverse.urls.authorize'),
            'urlAccessToken' => config('poniverse.urls.token'),
            'urlResourceOwnerDetails' => config('poniverse.urls.userinfo'),
            'scopes' => config('poniverse.scopes'),
            'scopeSeparator' => ' ',
            'pkceMethod' => AbstractProvider::PKCE_METHOD_S256,
        ]);
    }
}
