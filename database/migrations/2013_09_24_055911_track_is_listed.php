<?php

use Illuminate\Database\Migrations\Migration;

class TrackIsListed extends Migration
{
    public function up()
    {
        Schema::table('tracks', function ($table) {
            $table->boolean('is_listed')->notNullable()->indexed();
        });

        DB::update('
            UPDATE
                tracks
            SET
                is_listed = true');
    }

    public function down()
    {
        Schema::table('tracks', function ($table) {
            $table->dropColumn('is_listed');
        });
    }
}
