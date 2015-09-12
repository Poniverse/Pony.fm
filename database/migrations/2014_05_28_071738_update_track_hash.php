<?php

use App\Track;
use Illuminate\Database\Migrations\Migration;

class UpdateTrackHash extends Migration
{
    public function up()
    {
        foreach (Track::with('user')->get() as $track) {
            $track->updateHash();
            $track->save();
        }
    }

    public function down()
    {
    }
}