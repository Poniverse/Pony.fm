<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2026 Feld0.
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

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fills a development database with a large volume of plausible content:
 * artists, tracks (in every publish/list/flag state), albums, playlists,
 * comments, favourites and follows — so listings, pagination, filters,
 * profiles and the admin area all have something to chew on.
 *
 * Seeded tracks have no audio files, so they render everywhere but won't
 * stream; use real uploads to test playback. Run with:
 *
 *   php artisan db:seed --class="Database\Seeders\DevDataSeeder"
 *
 * Deterministic (seeded RNG), and additive — it never touches existing rows.
 */
class DevDataSeeder extends Seeder
{
    private const ARTISTS = 40;
    private const TRACKS = 500;
    private const ALBUM_RATIO = 0.45;   // chance an artist gets albums
    private const PLAYLISTS = 70;
    private const COMMENTS = 800;
    private const FAVOURITES = 1500;

    private array $adjectives = [
        'Lunar', 'Crystal', 'Everfree', 'Chromatic', 'Neon', 'Dusty', 'Velvet', 'Shattered', 'Golden', 'Midnight',
        'Prismatic', 'Sonic', 'Static', 'Harmonic', 'Cider', 'Thunder', 'Frozen', 'Electric', 'Wandering', 'Radiant',
    ];

    private array $nouns = [
        'Rainboom', 'Gala', 'Canter', 'Wing', 'Chord', 'Orchard', 'Skyline', 'Element', 'Echo', 'Stampede',
        'Aurora', 'Harvest', 'Marefall', 'Nocturne', 'Circuit', 'Meadow', 'Comet', 'Lullaby', 'Reverie', 'Anthem',
    ];

    private array $artistWords = [
        'Vinyl', 'Octavia', 'Nova', 'Glitch', 'Harmony', 'Zephyr', 'Ember', 'Static', 'Willow', 'Quill',
        'Fathom', 'Cinder', 'Tempo', 'Sable', 'Meadow', 'Drift', 'Pixel', 'Gale', 'Rune', 'Clover',
    ];

    private array $artistSuffixes = ['beat', 'wave', 'step', 'tone', 'dash', 'heart', 'strings', 'sound', 'works', 'song'];

    /** Canon show songs to remix (inserted only if missing). */
    private array $showSongs = [
        'Art of the Dress', 'At the Gala', 'Smile Song', 'This Day Aria', 'The Flim Flam Brothers',
        'Becoming Popular', 'Love Is In Bloom', 'Babs Seed', 'A True, True Friend', 'Hearts Strong as Horses',
        'Apples to the Core', 'Glass of Water', "You'll Play Your Part", 'The Spectacle', 'Rainbow',
    ];

    private array $commentPhrases = [
        'This is an instant favourite.', 'That drop at the halfway mark is unreal.', 'Been looping this all week!',
        'The melody gives me chills every time.', 'Incredible production on this one.', 'This deserves way more plays.',
        'Perfect for late night coding sessions.', 'The vocals are gorgeous.', 'I can hear the Daft Punk influence!',
        'Take my hooves, this is amazing.', 'Instant download.', 'How does this not have more favourites?',
        'The strings section is beautiful.', 'This grew on me so much.', 'Absolute classic in the making.',
    ];

    public function run(): void
    {
        mt_srand(20260816);
        $now = now();

        // --- Artists ---------------------------------------------------
        $userRows = [];
        $taken = DB::table('users')->pluck('slug')->all();
        for ($i = 0; $i < self::ARTISTS; $i++) {
            $name = $this->pick($this->artistWords).ucfirst($this->pick($this->artistSuffixes));
            $slug = Str::slug($name);
            if (in_array($slug, $taken, true)) {
                $name .= ' '.mt_rand(2, 99);
                $slug = Str::slug($name);
            }
            $taken[] = $slug;
            $userRows[] = [
                'display_name' => $name,
                'username' => $slug,
                'slug' => $slug,
                'email' => $slug.'@example.horse',
                'uses_gravatar' => true,
                'can_see_explicit_content' => (bool) mt_rand(0, 1),
                'bio' => mt_rand(0, 2) ? 'Making '.strtolower($this->pick($this->adjectives)).' pony tunes since '.mt_rand(2012, 2024).'.' : '',
                'created_at' => $now->copy()->subDays(mt_rand(30, 2000)),
                'updated_at' => $now,
            ];
        }
        DB::table('users')->insert($userRows);
        $userIds = DB::table('users')->whereIn('slug', array_column($userRows, 'slug'))->pluck('id')->all();

        $genreIds = DB::table('genres')->whereNull('deleted_at')->pluck('id')->all();
        $typeIds = DB::table('track_types')->pluck('id')->all();

        // --- Tracks ----------------------------------------------------
        $trackRows = [];
        for ($i = 0; $i < self::TRACKS; $i++) {
            $title = $this->pick($this->adjectives).' '.$this->pick($this->nouns);
            if (mt_rand(0, 2) === 0) {
                $title .= ' '.$this->pick(['(VIP)', 'II', '(Remix)', '(Acoustic)', 'Theme', '(Extended Mix)']);
            }
            $published = mt_rand(1, 100) <= 88;
            $createdAt = $now->copy()->subMinutes(mt_rand(60, 60 * 24 * 900));
            $plays = $this->longTail(30000);
            $trackRows[] = [
                'user_id' => $this->pick($userIds),
                'title' => $title,
                'slug' => Str::slug($title).'-'.$i,
                'description' => mt_rand(0, 1) ? 'A '.strtolower($this->pick($this->adjectives)).' journey through '.strtolower($this->pick($this->nouns)).' country.' : '',
                'lyrics' => mt_rand(0, 3) === 0 ? "Verse one goes here\nAnd the chorus follows on\nSeeded for testing" : '',
                'genre_id' => $this->pick($genreIds),
                'track_type_id' => $this->pick($typeIds),
                'is_vocal' => mt_rand(1, 100) <= 55,
                'is_explicit' => mt_rand(1, 100) <= 12,
                'is_downloadable' => mt_rand(1, 100) <= 85,
                'is_listed' => mt_rand(1, 100) <= 90,
                'duration' => mt_rand(75, 420),
                'play_count' => $published ? $plays : 0,
                'view_count' => $published ? (int) ($plays * (mt_rand(110, 220) / 100)) : 0,
                'download_count' => $published ? (int) ($plays * (mt_rand(2, 22) / 100)) : 0,
                'favourite_count' => 0,
                'comment_count' => 0,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
                'published_at' => $published ? $createdAt->copy()->addMinutes(mt_rand(10, 300)) : null,
                'released_at' => null,
                'is_latest' => false,
                'hash' => md5('dev-seed-track-'.$i),
                'current_version' => 1,
            ];
        }
        foreach (array_chunk($trackRows, 100) as $chunk) {
            DB::table('tracks')->insert($chunk);
        }
        $tracks = DB::table('tracks')->whereIn('user_id', $userIds)->get(['id', 'user_id', 'published_at', 'created_at']);

        // Flag each artist's newest published track.
        DB::statement('
            UPDATE tracks SET is_latest = true WHERE id IN (
                SELECT DISTINCT ON (user_id) id FROM tracks
                WHERE user_id = ANY(:ids) AND published_at IS NOT NULL AND deleted_at IS NULL
                ORDER BY user_id, published_at DESC
            )', ['ids' => '{'.implode(',', $userIds).'}']);

        // --- Albums ----------------------------------------------------
        $byUser = $tracks->groupBy('user_id');
        $albumCount = 0;
        foreach ($byUser as $userId => $userTracks) {
            if (mt_rand(1, 100) > self::ALBUM_RATIO * 100 || $userTracks->count() < 5) {
                continue;
            }
            $pool = $userTracks->shuffle()->values();
            $take = min(mt_rand(4, 12), $pool->count());
            $albumTracks = $pool->slice(0, $take)->values();
            $title = 'The '.$this->pick($this->adjectives).' '.$this->pick($this->nouns).' LP';
            $albumId = DB::table('albums')->insertGetId([
                'user_id' => $userId,
                'title' => $title,
                'slug' => Str::slug($title).'-'.$userId,
                'description' => 'A collection of '.strtolower($this->pick($this->adjectives)).' songs.',
                'track_count' => $albumTracks->count(),
                'view_count' => $this->longTail(4000),
                'created_at' => $now->copy()->subDays(mt_rand(5, 700)),
                'updated_at' => $now,
            ]);
            foreach ($albumTracks as $n => $t) {
                DB::table('tracks')->where('id', $t->id)->update(['album_id' => $albumId, 'track_number' => $n + 1]);
            }
            $albumCount++;
        }

        // --- Playlists -------------------------------------------------
        $publishedTrackIds = DB::table('tracks')->whereIn('user_id', $userIds)->whereNotNull('published_at')->pluck('id')->all();
        $allUserIds = array_merge([1], $userIds);
        for ($i = 0; $i < self::PLAYLISTS; $i++) {
            $title = $this->pick(['Mix:', 'Best of', 'Late Night', 'Gym', 'Study', 'Road Trip', 'Essential']).' '.$this->pick($this->nouns).' '.($i + 1);
            $ownerId = $this->pick($allUserIds);
            $picks = collect($publishedTrackIds)->shuffle()->take(mt_rand(3, 25))->values();
            $playlistId = DB::table('playlists')->insertGetId([
                'user_id' => $ownerId,
                'title' => $title,
                'slug' => Str::slug($title),
                'description' => mt_rand(0, 1) ? 'Seeded playlist for testing.' : '',
                'is_public' => mt_rand(1, 100) <= 80,
                'track_count' => $picks->count(),
                'view_count' => $this->longTail(2000),
                'created_at' => $now->copy()->subDays(mt_rand(1, 500)),
                'updated_at' => $now,
            ]);
            DB::table('playlist_track')->insert($picks->map(fn ($trackId, $n) => [
                'playlist_id' => $playlistId,
                'track_id' => $trackId,
                'position' => $n + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        }

        // --- Comments --------------------------------------------------
        $commentRows = [];
        for ($i = 0; $i < self::COMMENTS; $i++) {
            $commentRows[] = [
                'user_id' => $this->pick($allUserIds),
                'content' => $this->pick($this->commentPhrases),
                'track_id' => $this->pick($publishedTrackIds),
                'created_at' => $now->copy()->subMinutes(mt_rand(30, 60 * 24 * 400)),
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($commentRows, 200) as $chunk) {
            DB::table('comments')->insert($chunk);
        }
        DB::statement('UPDATE tracks SET comment_count = (SELECT COUNT(*) FROM comments WHERE comments.track_id = tracks.id AND comments.deleted_at IS NULL) WHERE id = ANY(:ids)',
            ['ids' => '{'.implode(',', $publishedTrackIds).'}']);

        // --- Favourites (incl. resource_users so userDetails scopes see them) ---
        $favRows = [];
        $seen = [];
        for ($i = 0; $i < self::FAVOURITES; $i++) {
            $userId = $this->pick($allUserIds);
            $trackId = $this->pick($publishedTrackIds);
            if (isset($seen[$userId.':'.$trackId])) {
                continue;
            }
            $seen[$userId.':'.$trackId] = true;
            $favRows[] = ['user_id' => $userId, 'track_id' => $trackId, 'created_at' => $now];
        }
        foreach (array_chunk($favRows, 200) as $chunk) {
            DB::table('favourites')->insert($chunk);
        }
        DB::table('resource_users')->insert(array_map(fn ($f) => [
            'user_id' => $f['user_id'],
            'track_id' => $f['track_id'],
            'is_favourited' => true,
        ], $favRows));
        DB::statement('UPDATE tracks SET favourite_count = (SELECT COUNT(*) FROM favourites WHERE favourites.track_id = tracks.id) WHERE id = ANY(:ids)',
            ['ids' => '{'.implode(',', $publishedTrackIds).'}']);

        // --- Show song remixes -------------------------------------------
        foreach ($this->showSongs as $title) {
            if (! DB::table('show_songs')->where('slug', Str::slug($title))->exists()) {
                DB::table('show_songs')->insert([
                    'title' => $title,
                    'slug' => Str::slug($title),
                    'lyrics' => '',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
        $showSongIds = DB::table('show_songs')->pluck('id')->all();
        $remixTrackIds = DB::table('tracks')->whereIn('user_id', $userIds)
            ->whereIn('track_type_id', [2, 5])->pluck('id');
        foreach ($remixTrackIds as $trackId) {
            foreach (collect($showSongIds)->shuffle()->take(mt_rand(1, 2)) as $showSongId) {
                DB::table('show_song_track')->insert(['track_id' => $trackId, 'show_song_id' => $showSongId]);
            }
        }

        // --- Recent listening activity (drives the "popular" ranking) ----
        $logRows = [];
        foreach (collect($publishedTrackIds)->shuffle()->take(80) as $trackId) {
            $bursts = mt_rand(2, 60);
            for ($n = 0; $n < $bursts; $n++) {
                $logRows[] = [
                    'track_id' => $trackId,
                    'log_type' => $this->pick([1, 1, 3, 3, 3, 2]),
                    'ip_address' => mt_rand(10, 250).'.'.mt_rand(0, 255).'.'.mt_rand(0, 255).'.'.mt_rand(1, 254),
                    'created_at' => $now->copy()->subMinutes(mt_rand(1, 60 * 23)),
                ];
            }
        }
        foreach (array_chunk($logRows, 500) as $chunk) {
            DB::table('resource_log_items')->insert($chunk);
        }

        // --- User 1 follows a handful of artists ------------------------
        foreach (collect($userIds)->shuffle()->take(8) as $artistId) {
            DB::table('followers')->insert(['user_id' => 1, 'artist_id' => $artistId, 'created_at' => $now]);
            DB::table('resource_users')->insert(['user_id' => 1, 'artist_id' => $artistId, 'is_followed' => true]);
        }

        // --- Refresh denormalized user counts ---------------------------
        DB::statement('UPDATE users SET track_count = (SELECT COUNT(*) FROM tracks WHERE tracks.user_id = users.id AND tracks.published_at IS NOT NULL AND tracks.deleted_at IS NULL),
            comment_count = (SELECT COUNT(*) FROM comments WHERE comments.user_id = users.id AND comments.deleted_at IS NULL)');

        $this->command?->info(sprintf(
            'Seeded %d artists, %d tracks (%d show-song remixes), %d albums, %d playlists, %d comments, %d favourites.',
            self::ARTISTS, self::TRACKS, $remixTrackIds->count(), $albumCount, self::PLAYLISTS, count($commentRows), count($favRows)
        ));
        $this->command?->warn('Seeded tracks have no audio files — playback only works on real uploads.');
    }

    private function pick(array $items)
    {
        return $items[mt_rand(0, count($items) - 1)];
    }

    /** Long-tail play counts: most tracks modest, a few hits. */
    private function longTail(int $max): int
    {
        return (int) floor(($max) * (mt_rand(0, 1000) / 1000) ** 4) + mt_rand(0, 120);
    }
}
