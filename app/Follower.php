<?php

namespace Poniverse\Ponyfm;

use Illuminate\Database\Eloquent\Model;

class Follower extends Model
{
    protected $table = 'followers';

    public $timestamps = false;
}