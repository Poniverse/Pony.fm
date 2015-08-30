<?php

use Illuminate\Database\Migrations\Migration;

class CreateLatestColumn extends Migration {
	public function up() {
		Schema::table('tracks', function($table) {
			$table->boolean('is_latest')->notNullable()->indexed();
		});

		DB::update('
			UPDATE
				tracks
			SET
				is_latest = true
			WHERE
				(
					SELECT
						t2.id
					FROM
						(SELECT id, user_id FROM tracks WHERE published_at IS NOT NULL AND deleted_at IS NULL) AS t2
					WHERE
						t2.user_id = tracks.user_id
					ORDER BY
						created_at DESC
					LIMIT 1
				) = tracks.id
			AND
				published_at IS NOT NULL');
	}

	public function down() {
		Schema::table('tracks', function($table) {
			$table->dropColumn('is_latest');
		});
	}
}