<?php

namespace Poniverse\Lib\Service;

use Poniverse\Lib\Errors\ApiException;
use Poniverse\Lib\Errors\Error;
use Psr\Http\Message\ResponseInterface;

class PonyfmService extends Service
{
    /**
     * @param ResponseInterface $response
     *
     * @throws ApiException
     */
    protected function handleError(ResponseInterface $response)
    {
        $body = json_decode($response->getBody(), true);

        $errorMessages = [];

        foreach ($body['errors'] as $type => $messages) {
            $errorMessages = array_merge($errorMessages, $messages);
        }

        $errors = array_map(function ($errorMessage) use ($body) {
            return new Error($body['title'], $errorMessage);
        }, $errorMessages);

        throw new ApiException($response->getStatusCode(), $errors);
    }
}
