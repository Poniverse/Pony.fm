<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAnnouncementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('text_content', 65535)->nullable();
            $table->integer('announcement_type_id')->unsigned()->nullable();
            $table->json('links')->nullable();
            $table->json('tracks')->nullable();
            $table->string('css_class')->nullable();
            $table->string('template_file')->nullable();
            $table->dateTime("start_time")->nullable();
            $table->dateTime("end_time")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('announcements');
    }
}
