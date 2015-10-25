<?php
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
