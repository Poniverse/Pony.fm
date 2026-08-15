<?php

namespace Poniverse\Lib\Service\Poniverse;

use Poniverse\Lib\Serializer\JsonApi;
use Poniverse\Lib\Service\JsonApiService;

class User extends JsonApiService
{
    /**
     * Gets a single user.
     *
     * @param $id
     *
     * @return \Poniverse\Lib\Entity\Poniverse\User
     */
    public function get($id)
    {
        $request = $this->request('get', $this->client->getPoniverseUrl().'/users/'.$id);

        $response = json_decode($request->getBody(), true);

        return new \Poniverse\Lib\Entity\Poniverse\User($response);
    }

    public function update(\Poniverse\Lib\Entity\Poniverse\User $user)
    {
        $request = $this->request('patch', $this->client->getPoniverseUrl().'/users/'.$user->id, [
            'body' => (new JsonApi())->serialize($user, 'users', true),
        ]);

        dd((string) $request->getBody(), $request);
    }
}
