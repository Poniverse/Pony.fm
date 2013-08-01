<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MigrateOldData extends Command {
	protected $name = 'migrate-old-data';
	protected $description = 'Migrates data from the old pfm site.';

	public function __construct()
	{
		parent::__construct();
	}

	public function fire() {
		$this->call('migrate:refresh');

		$oldDb = DB::connection('old');
		$oldUsers = $oldDb->table('users')->get();

		$this->info('Syncing Users');
		foreach ($oldUsers as $user) {
			$displayName = $user->display_name;
			if (!$displayName)
				$displayName = $user->username;

			if (!$displayName)
				$displayName = $user->mlpforums_name;

			if (!$displayName)
				continue;

			DB::table('users')->insert([
				'id' => $user->id,
				'display_name' => $displayName,
				'email' => $user->email,
				'created_at' => $user->created_at,
				'updated_at' => $user->updated_at,
				'slug' => $user->slug,
				'password_hash' => $user->password_hash,
				'password_salt' => $user->password_salt,
				'bio' => $user->bio,
				'sync_names' => $user->sync_names,
				'can_see_explicit_content' => $user->can_see_explicit_content,
				'mlpforums_name' => $user->mlpforums_name,
				'uses_gravatar' => $user->uses_gravatar,
				'gravatar' => $user->gravatar,
				'avatar_id' => null
			]);

			$coverId = null;
			if (!$user->uses_gravatar) {
				$coverFile = $this->getIdDirectory('users', $user->id) . '/' . $user->id . '_.png';
				$coverId = \Entities\Image::upload(new Symfony\Component\HttpFoundation\File\UploadedFile($coverFile, $user->id . '_.png'), $user->id)->id;
				DB::table('users')->where('id', $user->id)->update(['avatar_id' => $coverId]);
			}
		}

		$this->info('Syncing Genres');
		$oldGenres = $oldDb->table('genres')->get();
		foreach ($oldGenres as $genre) {
			DB::table('genres')->insert([
				'id' => $genre->id,
				'name' => $genre->title,
				'slug' => $genre->slug
			]);
		}

		$this->info('Syncing Albums');
		$oldAlbums = $oldDb->table('albums')->get();
		foreach ($oldAlbums as $playlist) {
			DB::table('albums')->insert([
				'title' => $playlist->title,
				'description' => $playlist->description,
				'created_at' => $playlist->created_at,
				'updated_at' => $playlist->updated_at,
				'deleted_at' => $playlist->deleted_at,
				'slug' => $playlist->slug,
				'id' => $playlist->id,
				'user_id' => $playlist->user_id
			]);
		}

		$this->info('Syncing Tracks');
		$oldTracks = $oldDb->table('tracks')->get();
		foreach ($oldTracks as $track) {
			$coverId = null;
			if ($track->cover) {
				$coverFile = $this->getIdDirectory('tracks', $track->id) . '/' . $track->id  . '_' . $track->cover . '.png';
				$coverId = \Entities\Image::upload(new Symfony\Component\HttpFoundation\File\UploadedFile($coverFile, $track->id . '_' . $track->cover . '.png'), $track->user_id)->id;
			}

			DB::table('tracks')->insert([
				'id' => $track->id,
				'title' => $track->title,
				'slug' => $track->slug,
				'description' => $track->description,
				'lyrics' => $track->lyrics,
				'created_at' => $track->created_at,
				'deleted_at' => $track->deleted_at,
				'updated_at' => $track->updated_at,
				'released_at' => $track->released_at,
				'published_at' => $track->published_at,
				'genre_id' => $track->genre_id,
				'is_explicit' => $track->explicit,
				'is_downloadable' => $track->downloadable,
				'is_vocal' => $track->is_vocal,
				'track_type_id' => $track->track_type_id,
				'track_number' => $track->track_number,
				'user_id' => $track->user_id,
				'album_id' => $track->album_id,
				'cover_id' => $coverId,
				'license_id' => $track->license_id
			]);
		}

		$oldShowSongs = $oldDb->table('song_track')->get();
		foreach ($oldShowSongs as $song) {
			DB::table('show_song_track')->insert([
				'id' => $song->id,
				'show_song_id' => $song->song_id,
				'track_id' => $song->track_id
			]);
		}

		$this->info('Syncing Playlists');
		$oldPlaylists = $oldDb->table('playlists')->get();
		foreach ($oldPlaylists as $playlist) {
			DB::table('playlists')->insert([
				'title' => $playlist->title,
				'description' => $playlist->description,
				'created_at' => $playlist->created_at,
				'updated_at' => $playlist->updated_at,
				'deleted_at' => $playlist->deleted_at,
				'slug' => $playlist->slug,
				'id' => $playlist->id,
				'user_id' => $playlist->user_id,
				'is_public' => true
			]);
		}

		$this->info('Syncing Playlist Tracks');
		$oldPlaylistTracks = $oldDb->table('playlist_track')->get();
		foreach ($oldPlaylistTracks as $playlistTrack) {
			DB::table('playlist_tracks')->insert([
				'id' => $playlistTrack->id,
				'created_at' => $playlistTrack->created_at,
				'updated_at' => $playlistTrack->updated_at,
				'position' => $playlistTrack->position,
				'playlist_id' => $playlistTrack->playlist_id,
				'track_id' => $playlistTrack->track_id
			]);
		}

		$this->info('Syncing Comments');
		$oldComments = $oldDb->table('comments')->get();
		foreach ($oldComments as $fav) {
			DB::table('comments')->insert([
				'id' => $fav->id,
				'user_id' => $fav->user_id,
				'created_at' => $fav->created_at,
				'deleted_at' => $fav->deleted_at,
				'updated_at' => $fav->updated_at,
				'content' => $fav->content,
				'track_id' => $fav->track_id,
				'album_id' => $fav->album_id,
				'playlist_id' => $fav->playlist_id,
				'profile_id' => $fav->profile_id
			]);
		}

		$this->info('Syncing Favourites');
		$oldFavs = $oldDb->table('favourites')->get();
		foreach ($oldFavs as $fav) {
			DB::table('favourites')->insert([
				'id' => $fav->id,
				'user_id' => $fav->user_id,
				'created_at' => $fav->created_at,
				'updated_at' => $fav->updated_at,
				'track_id' => $fav->track_id,
				'album_id' => $fav->album_id,
				'playlist_id' => $fav->playlist_id,
			]);
		}
	}

	private function getIdDirectory($type, $id) {
		$dir = (string) ( floor( $id / 100 ) * 100 );
		return \Config::get('app.files_directory') . '/' . $type . '/' . $dir;
	}

	protected function getArguments()
	{
		return [
			['example', InputArgument::REQUIRED, 'An example argument.'],
		];
	}

	protected function getOptions() {
		return [
			['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
		];
	}
}