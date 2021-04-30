<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Feld0.
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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailNotificationTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            // This table is used to enforce referential data integrity
            // for the polymorphic "activity" table.
            Schema::create('activity_types', function (Blueprint $table) {
                $table->unsignedTinyInteger('activity_type')->primary();
                $table->string('description');
            });

            DB::table('activity_types')->insert([
                ['activity_type' => 1, 'description' => 'news'],
                ['activity_type' => 2, 'description' => 'followee published a track'],
                ['activity_type' => 3, 'description' => 'followee published an album'],
                ['activity_type' => 4, 'description' => 'followee published a playlist'],
                ['activity_type' => 5, 'description' => 'new follower'],
                ['activity_type' => 6, 'description' => 'new comment'],
                ['activity_type' => 7, 'description' => 'new favourite'],
            ]);

            Schema::table('activities', function (Blueprint $table) {
                $table->foreign('activity_type')->references('activity_type')->on('activity_types');
            });

            Schema::create('email_subscriptions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('activity_type');
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('user_id')->references('id')->on('users');
                $table->foreign('activity_type')->references('activity_type')->on('activity_types');
            });

            Schema::create('emails', function (Blueprint $table) {
                $table->uuid('id')->primary();
                // Clicking the email link should mark the corresponding on-site notification as read.
                $table->unsignedBigInteger('notification_id')->index();
                $table->timestamps();

                $table->foreign('notification_id')->references('id')->on('notifications');
            });

            Schema::create('email_clicks', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('email_id')->index();
                $table->ipAddress('ip_address');
                $table->timestamps();

                $table->foreign('email_id')->references('id')->on('emails');
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::transaction(function () {
            Schema::drop('email_clicks');
            Schema::drop('emails');
            Schema::drop('email_subscriptions');

            Schema::table('activities', function (Blueprint $table) {
                $table->dropForeign('activities_activity_type_foreign');
            });

            Schema::drop('activity_types');
        });
    }
}
