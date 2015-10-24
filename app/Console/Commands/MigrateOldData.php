<?php

namespace Poniverse\Ponyfm\Console\Commands;

use Poniverse\Ponyfm\Image;
use Poniverse\Ponyfm\ResourceLogItem;
use DB;
use Exception;
use Illuminate\Console\Command;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MigrateOldData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate-old-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates data from the old pfm site.';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::connection()->disableQueryLog();

        $oldDb = DB::connection('old');

        $this->call('migrate:refresh');

        $oldUsers = $oldDb->table('users')->get();
        $this->info('Syncing Users');
        foreach ($oldUsers as $user) {
            $displayName = $user->display_name;
            if (!$displayName) {
                $displayName = $user->username;
            }

            if (!$displayName) {
                $displayName = $user->mlpforums_name;
            }

            if (!$displayName) {
                continue;
            }

            DB::table('users')->insert([
                'id' => $user->id,
                'display_name' => $displayName,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'slug' => $user->slug,
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
                try {
                    $coverFile = $this->getIdDirectory('users', $user->id) . '/' . $user->id . '_.png';
                    $coverId = Image::upload(new UploadedFile($coverFile,
                        $user->id . '_.png'), $user->id)->id;
                    DB::table('users')->where('id', $user->id)->update(['avatar_id' => $coverId]);
                } catch (\Exception $e) {
                    $this->error('Could copy user avatar ' . $user->id . ' because ' . $e->getMessage());
                    DB::table('users')->where('id', $user->id)->update(['uses_gravatar' => true]);
                }
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
            $logViews = $oldDb->table('album_log_views')->whereAlbumId($playlist->id)->get();
            $logDownload = $oldDb->table('album_log_downloads')->whereAlbumId($playlist->id)->get();

            DB::table('albums')->insert([
                'title' => $playlist->title,
                'description' => $playlist->description,
                'created_at' => $playlist->created_at,
                'updated_at' => $playlist->updated_at,
                'deleted_at' => $playlist->deleted_at,
                'slug' => $playlist->slug,
                'id' => $playlist->id,
                'user_id' => $playlist->user_id,
                'view_count' => 0,
                'download_count' => 0
            ]);

            foreach ($logViews as $logItem) {
                try {
                    DB::table('resource_log_items')->insert([
                        'user_id' => $logItem->user_id,
                        'log_type' => ResourceLogItem::VIEW,
                        'album_id' => $logItem->album_id,
                        'created_at' => $logItem->created_at,
                        'ip_address' => $logItem->ip_address,
                    ]);
                } catch (\Exception $e) {
                    $this->error('Could insert log item for album ' . $playlist->id . ' because ' . $e->getMessage());
                }
            }

            foreach ($logDownload as $logItem) {
                try {
                    DB::table('resource_log_items')->insert([
                        'user_id' => $logItem->user_id,
                        'log_type' => ResourceLogItem::DOWNLOAD,
                        'album_id' => $logItem->album_id,
                        'created_at' => $logItem->created_at,
                        'ip_address' => $logItem->ip_address,
                        'track_format_id' => $logItem->track_file_format_id - 1
                    ]);
                } catch (\Exception $e) {
                    $this->error('Could insert log item for album ' . $playlist->id . ' because ' . $e->getMessage());
                }
            }
        }

        $this->info('Syncing Tracks');
        $oldTracks = $oldDb->table('tracks')->get();
        foreach ($oldTracks as $track) {
            $coverId = null;
            if ($track->cover) {
                try {
                    $coverFile = $this->getIdDirectory('tracks',
                            $track->id) . '/' . $track->id . '_' . $track->cover . '.png';
                    $coverId = Image::upload(new UploadedFile($coverFile,
                        $track->id . '_' . $track->cover . '.png'), $track->user_id)->id;
                } catch (\Exception $e) {
                    $this->error('Could copy track cover ' . $track->id . ' because ' . $e->getMessage());
                }
            }

            $trackLogViews = $oldDb->table('track_log_views')->whereTrackId($track->id)->get();
            $trackLogPlays = $oldDb->table('track_log_plays')->whereTrackId($track->id)->get();
            $trackLogDownload = $oldDb->table('track_log_downloads')->whereTrackId($track->id)->get();

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
                'license_id' => $track->license_id,
                'duration' => $track->duration,
                'view_count' => 0,
                'play_count' => 0,
                'download_count' => 0
            ]);

            foreach ($trackLogViews as $logItem) {
                try {
                    DB::table('resource_log_items')->insert([
                        'user_id' => $logItem->user_id,
                        'log_type' => ResourceLogItem::VIEW,
                        'track_id' => $logItem->track_id,
                        'created_at' => $logItem->created_at,
                        'ip_address' => $logItem->ip_address
                    ]);
                } catch (\Exception $e) {
                    $this->error('Could insert log item for track ' . $track->id . ' because ' . $e->getMessage());
                }
            }

            foreach ($trackLogPlays as $logItem) {
                try {
                    DB::table('resource_log_items')->insert([
                        'user_id' => $logItem->user_id,
                        'log_type' => ResourceLogItem::PLAY,
                        'track_id' => $logItem->track_id,
                        'created_at' => $logItem->created_at,
                        'ip_address' => $logItem->ip_address
                    ]);
                } catch (\Exception $e) {
                    $this->error('Could insert log item for track ' . $track->id . ' because ' . $e->getMessage());
                }
            }

            foreach ($trackLogDownload as $logItem) {
                try {
                    DB::table('resource_log_items')->insert([
                        'user_id' => $logItem->user_id,
                        'log_type' => ResourceLogItem::DOWNLOAD,
                        'track_id' => $logItem->track_id,
                        'created_at' => $logItem->created_at,
                        'ip_address' => $logItem->ip_address,
                        'track_format_id' => $logItem->track_file_format_id - 1
                    ]);
                } catch (\Exception $e) {
                    $this->error('Could insert log item for track ' . $track->id . ' because ' . $e->getMessage());
                }
            }
        }

        $oldShowSongs = $oldDb->table('song_track')->get();
        foreach ($oldShowSongs as $song) {
            try {
                DB::table('show_song_track')->insert([
                    'id' => $song->id,
                    'show_song_id' => $song->song_id,
                    'track_id' => $song->track_id
                ]);
            } catch (\Exception $e) {
                $this->error('Could insert show track item for ' . $song->track_id . ' because ' . $e->getMessage());
            }
        }

        $this->info('Syncing Playlists');
        $oldPlaylists = $oldDb->table('playlists')->get();
        foreach ($oldPlaylists as $playlist) {
            $logViews = $oldDb->table('playlist_log_views')->wherePlaylistId($playlist->id)->get();
            $logDownload = $oldDb->table('playlist_log_downloads')->wherePlaylistId($playlist->id)->get();

            DB::table('playlists')->insert([
                'title' => $playlist->title,
                'description' => $playlist->description,
                'created_at' => $playlist->created_at,
                'updated_at' => $playlist->updated_at,
                'deleted_at' => $playlist->deleted_at,
                'slug' => $playlist->slug,
                'id' => $playlist->id,
                'user_id' => $playlist->user_id,
                'is_public' => true,
                'view_count' => 0,
                'download_count' => 0,
            ]);

            foreach ($logViews as $logItem) {
                try {
                    DB::table('resource_log_items')->insert([
                        'user_id' => $logItem->user_id,
                        'log_type' => ResourceLogItem::VIEW,
                        'playlist_id' => $logItem->playlist_id,
                        'created_at' => $logItem->created_at,
                        'ip_address' => $logItem->ip_address,
                    ]);
                } catch (\Exception $e) {
                    $this->error('Could insert log item for playlist ' . $playlist->id . ' because ' . $e->getMessage());
                }
            }

            foreach ($logDownload as $logItem) {
                try {
                    DB::table('resource_log_items')->insert([
                        'user_id' => $logItem->user_id,
                        'log_type' => ResourceLogItem::DOWNLOAD,
                        'playlist_id' => $logItem->playlist_id,
                        'created_at' => $logItem->created_at,
                        'ip_address' => $logItem->ip_address,
                        'track_format_id' => $logItem->track_file_format_id - 1
                    ]);
                } catch (\Exception $e) {
                    $this->error('Could insert log item for playlist ' . $playlist->id . ' because ' . $e->getMessage());
                }
            }
        }

        $this->info('Syncing Playlist Tracks');
        $oldPlaylistTracks = $oldDb->table('playlist_track')->get();
        foreach ($oldPlaylistTracks as $playlistTrack) {
            DB::table('playlist_track')->insert([
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
        foreach ($oldComments as $comment) {
            try {
                DB::table('comments')->insert([
                    'id' => $comment->id,
                    'user_id' => $comment->user_id,
                    'created_at' => $comment->created_at,
                    'deleted_at' => $comment->deleted_at,
                    'updated_at' => $comment->updated_at,
                    'content' => $comment->content,
                    'track_id' => $comment->track_id,
                    'album_id' => $comment->album_id,
                    'playlist_id' => $comment->playlist_id,
                    'profile_id' => $comment->profile_id
                ]);
            } catch (Exception $e) {
                $this->error('Could not sync comment ' . $comment->id . ' because ' . $e->getMessage());
            }
        }

        $this->info('Syncing Favourites');
        $oldFavs = $oldDb->table('favourites')->get();
        foreach ($oldFavs as $fav) {
            try {
                DB::table('favourites')->insert([
                    'id' => $fav->id,
                    'user_id' => $fav->user_id,
                    'created_at' => $fav->created_at,
                    'track_id' => $fav->track_id,
                    'album_id' => $fav->album_id,
                    'playlist_id' => $fav->playlist_id,
                ]);
            } catch (Exception $e) {
                $this->error('Could not sync favourite ' . $fav->id . ' because ' . $e->getMessage());
            }
        }

        $this->info('Syncing Followers');
        $oldFollowers = $oldDb->table('user_follower')->get();
        foreach ($oldFollowers as $follower) {
            try {
                DB::table('followers')->insert([
                    'id' => $follower->id,
                    'user_id' => $follower->follower_id,
                    'artist_id' => $follower->user_id,
                    'created_at' => $follower->created_at,
                ]);
            } catch (Exception $e) {
                $this->error('Could not sync follower ' . $follower->id . ' because ' . $e->getMessage());
            }
        }
    }

    private function getIdDirectory($type, $id)
    {
        $dir = (string)(floor($id / 100) * 100);

        return \Config::get('ponyfm.files_directory') . '/' . $type . '/' . $dir;
    }

}
