<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUnclassifiedTrackType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('track_types')->insert([
            'id' => 6,
            'title' => 'Unclassified',
            'editor_title' => 'an unclassified track'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('track_types')->where('id', 6)->delete();
    }
}
