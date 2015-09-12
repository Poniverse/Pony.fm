<?php

use Illuminate\Database\Migrations\Migration;

class Oauth extends Migration
{
    public function up()
    {
        Schema::create('oauth2_tokens', function ($table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('external_user_id');
            $table->text('access_token');
            $table->timestamp('expires');
            $table->text('refresh_token');
            $table->string('type');
            $table->string('service');
        });
    }

    public function down()
    {
        Schema::drop('oauth2_tokens');
    }
}