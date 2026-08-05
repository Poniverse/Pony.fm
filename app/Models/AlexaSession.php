<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\AlexaSession.
 *
 * @property string $id
 * @property string $payload
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AlexaSession whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AlexaSession wherePayload($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AlexaSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AlexaSession whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AlexaSession extends Model
{
    public $incrementing = false;

    protected $table = 'alexa_session';

    protected $casts = [
        'payload' => 'array',
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
