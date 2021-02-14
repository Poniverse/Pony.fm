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

namespace Poniverse\Ponyfm\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use League\OAuth2\Client\Token\AccessToken;
use Poniverse;
use Poniverse\Lib\Client;
use Poniverse\Ponyfm\Models\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateOAuth
{
    /**
     * @var Client
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

    public function __construct(Guard $auth, Store $session)
    {
        $this->poniverse = new Client(config('poniverse.client_id'), config('poniverse.secret'), new \GuzzleHttp\Client());
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
     * @throws Poniverse\Lib\Errors\ApiException
     */
    public function handle(Request $request, Closure $next, $requiredScope)
    {
        // Ensure this is a valid OAuth client.
        $accessToken = $this->determineAccessToken($request, false);

        // check that access token is valid at Poniverse.net
        $accessTokenInfo = $this->poniverse->poniverse()->meta()->introspect($accessToken);

        if (! $accessTokenInfo->getIsActive()) {
            throw new AccessDeniedHttpException('This access token is expired or invalid!');
        }

        if (! in_array($requiredScope, $accessTokenInfo->getScopes())) {
            throw new AccessDeniedHttpException("This access token lacks the '${requiredScope}' scope!");
        }

        // Log in as the given user, creating the account if necessary.
        $this->poniverse->setAccessToken(new AccessToken(['access_token' => $accessToken]));
        $this->session->put('api_client_id', $accessTokenInfo->getClientId());

        /** @var Poniverse\Lib\Entity\Poniverse\User $poniverseUser */
        $poniverseUser = $this->poniverse->getOAuthProvider()->getResourceOwner($accessToken);

        $user = User::findOrCreate($poniverseUser->username, $poniverseUser->display_name, $poniverseUser->email);
        $this->auth->setUser($user);

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
