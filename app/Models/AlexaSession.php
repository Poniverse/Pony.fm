<?php

namespace Poniverse\Ponyfm\Models;

use Illuminate\Database\Eloquent\Model;

class AlexaSession extends Model
{
    public $incrementing = false;

    protected $table = 'alexa_session';

    protected $casts = [
        'payload' => 'array'
    ];

    public function put($key, $value)
    {
        $payload = $this->payload;

        $payload[$key] = $value;

        $this->payload = $payload;
    }

    public function get($key, $default = null)
    {
        return $this->payload[$key] ?? $default;
    }
}
