<?php

/**
 * From the venturecraft/revisionable package: https://github.com/VentureCraft/revisionable.
 *
 * Modified to add a foreign key constraint on the `user_id` column and
 * designate the `revisionable_id` and `user_id` columns as unsigned.
 */

use Illuminate\Database\Migrations\Migration;

class CreateCustomizedRevisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('revisions', function ($table) {
            $table->increments('id');
            $table->string('revisionable_type');
            $table->integer('revisionable_id')->unsigned();
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('key');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamps();

            $table->index(['revisionable_id', 'revisionable_type']);

            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('revisions');
    }
}
