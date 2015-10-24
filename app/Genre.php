<?php

namespace Poniverse\Ponyfm;

use Poniverse\Ponyfm\Traits\SlugTrait;
use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    protected $table = 'genres';
    protected $fillable = ['name', 'slug'];
    public $timestamps = false;

    use SlugTrait;
}