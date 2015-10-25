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

/**
 * Class Poniverse
 *
 * Just for the sake of being sane without an autoloader
 * this class is going to be a simple flat api class.
 */

class Poniverse {
    protected $clientId;
    protected $clientSecret;
    protected $accessToken;
    protected $redirectUri;
    protected $urls;

    /**
     * @var OAuth2\Client
     */
    protected $client;

    /**
     * Initialises the class
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $accessToken
     */
    public function __construct($clientId, $clientSecret, $accessToken = '')
    {
        $this->urls = Config::get('poniverse.urls');

        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;
        $this->accessToken  = $accessToken;

        //Setup Dependencies
        $this->setupOAuth2();
        $this->setupHttpful();
    }

    protected function setupOAuth2()
    {
        require_once('oauth2/Client.php');
        require_once('oauth2/GrantType/IGrantType.php');
        require_once('oauth2/GrantType/AuthorizationCode.php');

        $this->client = new \OAuth2\Client($this->clientId, $this->clientSecret);
    }

    protected function setupHttpful()
    {
        require_once('autoloader.php');
        $autoloader = new SplClassLoader('Httpful', __DIR__."/httpful/src/");
        $autoloader->register();
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function getAuthenticationUrl($state)
    {
        return $this->client->getAuthenticationUrl($this->urls['auth'], $this->redirectUri, ['state' => $state]);
    }

    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = $redirectUri;
    }

    /**
     * Gets the OAuth2 Client
     *
     * @return \OAuth2\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Gets data about the currently logged in user
     *
     * @return array
     */
    public function getUser()
    {
        $data = \Httpful\Request::get($this->urls['api'] . "users?access_token=" . $this->accessToken );

        $result = $data->addHeader('Accept', 'application/json')->send();

        return json_decode($result, true);
    }
}
