<?php

use Illuminate\Database\Migrations\Migration;

class CreateNewsTable extends Migration
{
    public function up()
    {
        Schema::create('news', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index();
            $table->string('post_hash', 32)->index();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::drop('news');
    }

}