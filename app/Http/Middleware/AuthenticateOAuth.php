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

namespace Poniverse\Ponyfm\Http\Middleware;

use Closure;
use GuzzleHttp;
use Illuminate\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Poniverse;
use Poniverse\Ponyfm\Models\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateOAuth
{
    /**
     * @var Poniverse
     */
    private $poniverse;

    /**
     * @var Guard
     */
    private $auth;

    /**
     * @var Store
     */
    private $session;

    public function __construct(Poniverse $poniverse, Guard $auth, Store $session) {
        $this->poniverse = $poniverse;
        $this->auth = $auth;
        $this->session = $session;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  string $requiredScope
     * @return mixed
     * @throws \OAuth2\Exception
     */
    public function handle(Request $request, Closure $next, $requiredScope)
    {
        // Ensure this is a valid OAuth client.
        $accessToken = $this->determineAccessToken($request, false);

        // check that access token is valid at Poniverse.net
        $accessTokenInfo = $this->poniverse->getAccessTokenInfo($accessToken);

        if (!$accessTokenInfo->getIsActive()) {
            throw new AccessDeniedHttpException('This access token is expired or invalid!');
        }

        if (!in_array($requiredScope, $accessTokenInfo->getScopes())) {
            throw new AccessDeniedHttpException("This access token lacks the '${requiredScope}' scope!");
        }

        // Log in as the given user, creating the account if necessary.
        $this->poniverse->setAccessToken($accessToken);
        $this->session->put('api_client_id', $accessTokenInfo->getClientId());

        $poniverseUser = $this->poniverse->getUser();

        $user = User::findOrCreate($poniverseUser['username'], $poniverseUser['display_name'], $poniverseUser['email']);
        $this->auth->onceUsingId($user);

        return $next($request);
    }


    private function determineAccessToken(Request $request, $headerOnly = true)
    {
        $header = $request->header('Authorization');

        if ($header !== null && substr($header, 0, 7) === 'Bearer ') {
            return trim(substr($header, 7));
        }

        if ($headerOnly) {
            return null;
        }

        return $request->get('access_token');
    }
}
