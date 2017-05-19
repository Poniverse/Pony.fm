<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('report_categories', function (Blueprint $table) {
            $table->unsignedTinyInteger('report_category')->primary();
            $table->string('name');
            $table->string('description');
        });

        DB::table('report_categories')->insert([
            ['report_category' => 1, 'name' => 'Copyright Infringement', 'description' => 'Such as re-uploading other people\'s tracks without permission'],
            ['report_category' => 2, 'name' => 'Harassment', 'description' => 'Attacks or threats on individuals through lyrics, repeated harassing comments, etc'],
            ['report_category' => 3, 'name' => 'Offensive', 'description' => 'Includes hate speech, extremely vulgar language and sexual content'],
            ['report_category' => 4, 'name' => 'Spam', 'description' => 'Spam and advertising are not allowed. You may advertise your social media in your descriptions but don\'t spam tracks or comments'],
            ['report_category' => 5, 'name' => 'Non-Pony', 'description' => 'We only host pony music and we are removing anything that is clearly non-pony. Pony inspired tracks are fine however'],
        ]);

        Schema::create('reports', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('reporter_id')->unsigned();
            $table->integer('resource_type')->unsigned();
            $table->integer('resource_id')->unsigned();
            $table->unsignedTinyInteger('category');
            $table->text('message', 65535);
            $table->timestamps();
            $table->dateTime('resolved_at')->nullable()->index();

            $table->foreign('reporter_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('category')->references('report_category')->on('report_categories');
        });

        Schema::create('report_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->integer('report_id')->unsigned();
            $table->text('message', 65535);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('report_id')->references('id')->on('reports')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('report_categories');
        Schema::dropIfExists('report_messages');
    }
}
