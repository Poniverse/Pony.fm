<?php

namespace Poniverse\Ponyfm;

use Illuminate\Database\Eloquent\Model;

class TrackType extends Model
{
    protected $table = 'track_types';

    const ORIGINAL_TRACK = 1;
    const OFFICIAL_TRACK_REMIX = 2;
    const FAN_TRACK_REMIX = 3;
    const PONIFIED_TRACK = 4;
    const OFFICIAL_AUDIO_REMIX = 5;
}