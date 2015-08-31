<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait SlugTrait
{
    public function setTitleAttribute($value)
    {
        $this->slug = Str::slug($value);
        $this->attributes['title'] = $value;
    }
}