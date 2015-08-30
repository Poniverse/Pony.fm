<?php

use Illuminate\Database\Eloquent\Model;
use Traits\SlugTrait;

class Genre extends Model
{
    protected $table = 'genres';

    use SlugTrait;
}