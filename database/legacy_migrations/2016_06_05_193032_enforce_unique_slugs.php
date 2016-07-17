<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EnforceUniqueSlugs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Fix slugs retroactively
        $slugs = DB::table('users')
            ->select(['slug', DB::raw('COUNT(*)')])
            ->groupBy('slug')
            ->havingRaw('COUNT(*) > 1')
            ->lists('slug');

        foreach ($slugs as $slug) {
            DB::transaction(function () use ($slug) {
                // find users with that slug, ordered by published content
                $users = DB::table('users')
                    ->select([
                        'id',
                        'slug',
                        'disabled_at',
                        DB::raw('(SELECT COUNT(*) FROM tracks WHERE tracks.user_id = users.id AND published_at IS NOT NULL AND deleted_at IS NULL) as track_count'),
                        DB::raw('(SELECT COUNT(*) FROM albums WHERE albums.user_id = users.id AND deleted_at IS NULL AND albums.track_count > 1) as album_count'),
                        DB::raw('(SELECT COUNT(*) FROM playlists WHERE playlists.user_id = users.id AND deleted_at IS NULL AND playlists.track_count > 1) as playlist_count'),
                    ])
                    ->where('slug', $slug)
                    ->orderBy('disabled_at', 'ASC')
                    ->orderBy('track_count', 'DESC')
                    ->orderBy('playlist_count', 'DESC')
                    ->orderBy('album_count', 'DESC')
                    ->get();

                // ensure a unique slug for each
                $isOriginalSlugTaken = false;
                $counter = 2;
                foreach($users as $user) {
                    if (false === $isOriginalSlugTaken) {
                        // This lucky user gets to keep the original slug!
                        $isOriginalSlugTaken = true;
                        continue;
                    } else {
                        $now = \Carbon\Carbon::now();
                        $newSlug = "{$slug}-{$counter}";

                        DB::table('revisions')
                            ->insert([
                                'revisionable_type' => 'Poniverse\\Ponyfm\\Models\\User',
                                'revisionable_id'   => $user->id,
                                'user_id'           => null,
                                'key'               => 'slug',
                                'old_value'         => $slug,
                                'new_value'         => $newSlug,
                                'created_at'        => $now,
                                'updated_at'        => $now,
                            ]);

                        DB::table('users')
                            ->where('id', $user->id)
                            ->update([
                                'slug'          => $newSlug,
                                'updated_at'    => $now
                            ]);

                        $counter++;
                    }
                }
            });
        }

        Schema::table('users', function(Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_slug_unique');
        });
    }
}
