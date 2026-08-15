<?php

namespace Poniverse\Lib\Service\Poniverse;

use Poniverse\Lib\Client;

class Factory
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Returns the user service.
     *
     * @return User
     */
    public function users()
    {
        return new User($this->client);
    }

    /**
     * Returns the access token service.
     *
     * @return Meta
     */
    public function meta() {
        return new Meta($this->client);
    }
}
