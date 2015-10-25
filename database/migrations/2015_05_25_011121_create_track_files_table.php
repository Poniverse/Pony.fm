<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Peter Deltchev
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

use Poniverse\Ponyfm\Track;
use Illuminate\Database\Migrations\Migration;


class CreateTrackFilesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Fill in the table
        DB::transaction(function () {
            Schema::create('track_files', function ($table) {
                $table->increments('id');
                $table->integer('track_id')->unsigned()->indexed();
                $table->boolean('is_master')->default(false)->indexed();
                $table->string('format')->indexed();

                $table->foreign('track_id')->references('id')->on('tracks');
                $table->timestamps();
            });

            foreach (Track::all() as $track) {
                foreach (Track::$Formats as $name => $item) {
                    DB::table('track_files')->insert(
                        [
                            'track_id' => $track->id,
                            'is_master' => $name === 'FLAC' ? true : false,
                            'format' => $name,
                            'created_at' => $track->created_at,
                            'updated_at' => Carbon\Carbon::now()
                        ]
                    );
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('track_files');
    }

}
