<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateLicensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 100);
            $table->text('description', 65535);
            $table->boolean('affiliate_distribution');
            $table->boolean('open_distribution');
            $table->boolean('remix');
        });

        DB::table('licenses')->insert([
            'id' => 1,
            'title' => 'Personal',
            'description' => 'Only you and Pony.fm are allowed to distribute and broadcast the track.',
            'affiliate_distribution' => 0,
            'open_distribution' => 0,
            'remix' => 0,
        ]);

        DB::table('licenses')->insert([
            'id' => 2,
            'title' => 'Broadcast',
            'description' => 'You, Pony.fm, and its affiliates may distribute and broadcast the track.',
            'affiliate_distribution' => 1,
            'open_distribution' => 0,
            'remix' => 0,
        ]);

        DB::table('licenses')->insert([
            'id' => 3,
            'title' => 'Open',
            'description' => 'Anyone is permitted to broadcast and distribute the song in its original form, with attribution to you.',
            'affiliate_distribution' => 1,
            'open_distribution' => 1,
            'remix' => 0,
        ]);

        DB::table('licenses')->insert([
            'id' => 4,
            'title' => 'Remix',
            'description' => 'Anyone is permitted to broadcast and distribute the song in any form, or create derivative works based on it for any purpose, with attribution to you.',
            'affiliate_distribution' => 1,
            'open_distribution' => 1,
            'remix' => 1,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('licenses');
    }
}
