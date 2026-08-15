<?php

namespace Poniverse\Lib\Service;

use Poniverse\Lib\Errors\ApiException;
use Poniverse\Lib\Errors\Error;
use Psr\Http\Message\ResponseInterface;

class JsonApiService extends Service
{
    /**
     * @param ResponseInterface $response
     *
     * @throws ApiException
     */
    protected function handleError(ResponseInterface $response)
    {
        $body = json_decode($response->getBody(), true);

        $errors = [];

        if (isset($body['errors'])) {
            $errors = array_map(function ($error) {
                return new Error($error['title'], $error['detail']);
            }, $body['errors']);
        }

        throw new ApiException($response->getStatusCode(), $errors);
    }
}
