<?php

namespace Poniverse\Lib\Entity\Poniverse;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Poniverse\Lib\Entity\Entity;

/**
 * Poniverse\Lib\Entity\Poniverse\User.
 *
 * @property string $username
 * @property string $display_name
 * @property string $email
 */
class User extends Entity implements ResourceOwnerInterface
{
    protected $readOnlyProperties = ['id', 'username'];

    public function hydrate(array $data)
    {
        $this->id = $data['id'];
        $this->attributes = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return array_merge(['id' => $this->id], $this->data);
    }
}
