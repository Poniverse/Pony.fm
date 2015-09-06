<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\SlugTrait;

class Genre extends Model
{
    protected $table = 'genres';

    use SlugTrait;
}