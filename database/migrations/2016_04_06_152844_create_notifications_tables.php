<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Peter Deltchev
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activities', function(Blueprint $table){
            $table->bigIncrements('id');
            $table->dateTime('created_at')->index();
            $table->unsignedInteger('user_id'); // initiator of the action
            $table->unsignedTinyInteger('activity_type');
            $table->unsignedTinyInteger('resource_type');
            $table->unsignedInteger('resource_id'); // ID of the entity this activity is about
        });

        Schema::create('notifications', function(Blueprint $table){
            // Notifications are a pivot table between activities and users.
            $table->bigIncrements('id');
            $table->unsignedBigInteger('activity_id')->index();
            $table->unsignedInteger('user_id')->index(); // recipient of the notification
            $table->boolean('is_read')->default(false)->index();
            
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
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
        Schema::drop('notifications');
        Schema::drop('activities');
    }
}
