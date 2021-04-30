<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateShowSongsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('show_songs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 100)->index('show_songs_title_fulltext');
            $table->text('lyrics', 65535);
            $table->string('slug', 200);
            $table->timestamps();
            $table->timestamp('deleted_at')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('show_songs');
    }
}
