<?php

use Illuminate\Database\Migrations\Migration;
use Entities\Track;
use Entities\TrackFile;

class CreateTrackFilesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		// Fill in the table
		DB::transaction(function(){
			Schema::create('track_files', function($table){
				$table->increments('id');
				$table->integer('track_id')->unsigned()->indexed();
				$table->boolean('is_master')->default(false)->indexed();
				$table->string('format')->indexed();

				$table->foreign('track_id')->references('id')->on('tracks');
				$table->timestamps();
			});

			foreach (Track::all() as $track){
				foreach (Track::$Formats as $name => $item) {
					DB::table('track_files')->insert(
						[
							'track_id'  => $track->id,
							'is_master' => $name === 'FLAC' ? true : false,
							'format'    => $name,
							'created_at'=> $track->created_at,
							'updated_at'=> Carbon\Carbon::now()
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