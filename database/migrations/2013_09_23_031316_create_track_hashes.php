<?php

use App\Track;
use Illuminate\Database\Migrations\Migration;

class CreateTrackHashes extends Migration
{
    public function up()
    {
        Schema::table('tracks', function ($table) {
            $table->string('hash', 32)->notNullable()->indexed();
        });

        foreach (Track::with('user')->get() as $track) {
            $track->updateHash();
            $track->save();
        }
    }

    public function down()
    {
        Schema::table('tracks', function ($table) {
            $table->dropColumn('hash');
        });
    }
}