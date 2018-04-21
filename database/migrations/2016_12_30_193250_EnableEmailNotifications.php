<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Feld0
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


use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Poniverse\Ponyfm\Models\User;

class EnableEmailNotifications extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        DB::table('email_subscriptions')->delete();

        User::whereNull('disabled_at')
            ->where('is_archived', false)
            ->chunk(100, function ($users) {
            /** @var User $user */
            foreach ($users as $user) {
                $now = \Carbon\Carbon::now();
                $userId = $user->id;

                DB::table('email_subscriptions')
                  ->insert([
                      [
                          'id' => \Webpatser\Uuid\Uuid::generate(4),
                          'user_id' => $userId,
                          'activity_type' => 2,
                          'created_at' => $now,
                          'updated_at' => $now,
                      ],
                      [
                          'id' => \Webpatser\Uuid\Uuid::generate(4),
                          'user_id' => $userId,
                          'activity_type' => 3,
                          'created_at' => $now,
                          'updated_at' => $now,
                      ],
                      [
                          'id' => \Webpatser\Uuid\Uuid::generate(4),
                          'user_id' => $userId,
                          'activity_type' => 4,
                          'created_at' => $now,
                          'updated_at' => $now,
                      ],
                      [
                          'id' => \Webpatser\Uuid\Uuid::generate(4),
                          'user_id' => $userId,
                          'activity_type' => 5,
                          'created_at' => $now,
                          'updated_at' => $now,
                      ],
                      [
                          'id' => \Webpatser\Uuid\Uuid::generate(4),
                          'user_id' => $userId,
                          'activity_type' => 6,
                          'created_at' => $now,
                          'updated_at' => $now,
                      ],
                      [
                          'id' => \Webpatser\Uuid\Uuid::generate(4),
                          'user_id' => $userId,
                          'activity_type' => 7,
                          'created_at' => $now,
                          'updated_at' => $now,
                      ]
                  ]);
            }

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        DB::table('email_subscriptions')->delete();
    }
}
