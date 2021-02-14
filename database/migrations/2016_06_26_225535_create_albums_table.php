<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAlbumsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('albums', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index('albums_user_id_foreign');
            $table->string('title')->index();
            $table->string('slug')->index();
            $table->text('description', 65535);
            $table->integer('cover_id')->unsigned()->nullable()->index('albums_cover_id_foreign');
            $table->integer('track_count')->unsigned()->default(0);
            $table->integer('view_count')->unsigned()->default(0);
            $table->integer('download_count')->unsigned()->default(0);
            $table->integer('favourite_count')->unsigned()->default(0);
            $table->integer('comment_count')->unsigned()->default(0);
            $table->timestamps();
            $table->softDeletes()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('albums');
    }
}
