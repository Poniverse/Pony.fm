<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->bigInteger('id', true)->unsigned();
            $table->dateTime('created_at')->index();
            $table->integer('user_id')->unsigned();
            $table->unsignedTinyInteger('activity_type');
            $table->unsignedTinyInteger('resource_type');
            $table->integer('resource_id')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('activities');
    }
}
