<?php

namespace Poniverse\Lib\Serializer;

use Poniverse\Lib\Entity\Entity;

class JsonApi
{
    public function serialize(Entity $entity, $type, $dirtyOnly = false)
    {
        $base = [
            'data' => [
                'type' => $type,
                'id' => $entity->id,
                'attributes' => $dirtyOnly ? $entity->getDirtyAttributes() : $entity->getDirtyAttributes(),
            ],
        ];

        return json_encode($base);
    }
}
