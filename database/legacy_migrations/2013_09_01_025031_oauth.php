<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0
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
