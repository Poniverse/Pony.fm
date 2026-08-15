<?php

namespace Poniverse\Lib;

use GuzzleHttp\Client as HttpClient;
use League\OAuth2\Client\Token\AccessToken;
use Poniverse\Lib\OAuth2\PoniverseProvider;

class Client
{
    /**
     * @var string|null
     */
    protected $accessToken = null;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * Poniverse.net Client ID.
     *
     * @var string
     */
    private $clientId;

    /**
     * Poniverse.net Client Secret.
     *
     * @var string
     */
    private $clientSecret;

    /**
     * Poniverse project url mappings.
     *
     * @var array
     */
    protected $urlMappings = [
        'poniverse' => 'https://api.poniverse.net',
        'ponyfm' => 'https://pony.fm',
    ];

    /**
     * Initializes the Poniverse Api client.
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param \GuzzleHttp\Client $httpClient
     * @param array $urlOverrides
     */
    public function __construct($clientId, $clientSecret, HttpClient $httpClient, $urlOverrides = [])
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->httpClient = $httpClient;

        if (count($urlOverrides) > 0) {
            $this->urlMappings = array_merge($this->urlMappings, $urlOverrides);
        }
    }

    /**
     * Returns the set access key.
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Sets the access token to communicate to the api with.
     *
     * @param $accessToken
     *
     * @return $this
     */
    public function setAccessToken(AccessToken $accessToken)
    {
        $this->accessToken = $accessToken->getToken();

        return $this;
    }

    /**
     * Returns the http client.
     *
     * @return \GuzzleHttp\Client
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * Returns the Autho.
     *
     * @return array
     */
    public function getAuthHeader()
    {
        return ['Authorization' => 'Bearer '.$this->getAccessToken()];
    }

    public function getPoniverseUrl()
    {
        return $this->urlMappings['poniverse'];
    }

    public function getPonyfmUrl()
    {
        return $this->urlMappings['ponyfm'];
    }

    /**
     * Returns the Poniverse.net service factory.
     *
     * @return Service\Poniverse\Factory
     */
    public function poniverse()
    {
        return new Service\Poniverse\Factory($this);
    }

    /**
     * Returns the Poniverse OAuth Provider class.
     *
     * @param array $options
     * @return PoniverseProvider
     */
    public function getOAuthProvider(array $options = [])
    {
        $options = array_merge([
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
        ], $options);

        return new PoniverseProvider($options);
    }
}
