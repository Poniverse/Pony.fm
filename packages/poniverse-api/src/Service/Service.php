<?php

namespace Poniverse\Lib\Service;

use GuzzleHttp\Exception\RequestException;
use Poniverse\Lib\Client;
use Poniverse\Lib\Errors\ApiException;
use Psr\Http\Message\ResponseInterface;

abstract class Service
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array  $options
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws ApiException
     */
    protected function request($method, $url, array $options = [])
    {
        $options = array_merge(['headers' => $this->client->getAuthHeader()], $options);

        try {
            return $this->client->getHttpClient()->request(
                $method,
                $url,
                $options
            );
        } catch (RequestException $e) {
            $this->handleError($e->getResponse());
        }
    }

    /**
     * @param ResponseInterface $response
     *
     * @throws ApiException
     */
    abstract protected function handleError(ResponseInterface $response);
}
