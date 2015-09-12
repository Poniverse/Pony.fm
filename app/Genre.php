<?php

namespace App;

use App\Traits\SlugTrait;
use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    protected $table = 'genres';
    protected $fillable = ['name', 'slug'];
    public $timestamps = false;

    use SlugTrait;
}