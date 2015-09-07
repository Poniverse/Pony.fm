<?php

use Illuminate\Database\Migrations\Migration;

class AddNewIndices extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE `show_songs` ADD FULLTEXT show_songs_title_fulltext (title)');

		Schema::table('images', function ($table) {
			$table->index('hash');
		});

		Schema::table('track_files', function ($table) {
			$table->index('is_master');
			$table->index('format');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE `show_songs` DROP INDEX show_songs_title_fulltext');

		Schema::table('images', function ($table) {
			$table->dropIndex('images_hash_index');
		});

		Schema::table('track_files', function ($table) {
			$table->dropIndex('track_files_is_master_index');
			$table->dropIndex('track_files_format_index');
		});
	}

}
