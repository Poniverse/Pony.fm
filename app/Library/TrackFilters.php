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

use App\Models\Track;

/**
 * The `?filter=vocal-yes!genres-3-7!sort-plays` grammar the Angular app used
 * for shareable track-listing URLs, parsed and applied server-side so the
 * Inertia tracks page can be rendered (and SSR'd) from the URL alone.
 */
class TrackFilters
{
    public const PER_PAGE = 45;

    /** sort key => [column, direction]; '' is the default (latest).
     *  'trending' has no column — it's handled specially in listTracks. */
    public const SORTS = [
        '' => ['published_at', 'desc'],
        'trending' => null,
        'plays' => ['play_count', 'desc'],
        'downloads' => ['download_count', 'desc'],
        'favourites' => ['favourite_count', 'desc'],
        'alphabetical' => ['title', 'asc'],
    ];

    public const ARCHIVES = ['eqbeats', 'mlpma', 'ponify'];

    /**
     * @return array{vocal: ?string, sort: string, genres: int[], types: int[], songs: int[], archive: ?string}
     */
    public static function parse(?string $filterString): array
    {
        $filters = [
            'vocal' => null,
            'sort' => '',
            'genres' => [],
            'types' => [],
            'songs' => [],
            'archive' => null,
        ];

        foreach (explode('!', $filterString ?? '') as $part) {
            $tokens = explode('-', $part);
            $name = array_shift($tokens);
            $value = $tokens[0] ?? '';

            switch ($name) {
                case 'vocal':
                    if (in_array($value, ['yes', 'no'], true)) {
                        $filters['vocal'] = $value;
                    }
                    break;
                case 'sort':
                    if (array_key_exists($value, self::SORTS)) {
                        $filters['sort'] = $value;
                    }
                    break;
                case 'archive':
                    if (in_array($value, self::ARCHIVES, true)) {
                        $filters['archive'] = $value;
                    }
                    break;
                case 'genres':
                case 'types':
                case 'songs':
                    $filters[$name] = array_values(array_filter(array_map('intval', $tokens)));
                    break;
            }
        }

        return $filters;
    }

    /**
     * List published tracks for the public browse page.
     *
     * @return array{tracks: array, current_page: int, total_pages: int}
     */
    public static function listTracks(array $filters, int $page, bool $random = false): array
    {
        $query = Track::summary()
            ->userDetails()
            ->listed()
            ->explicitFilter()
            ->published()
            ->with('user', 'genre', 'cover', 'album', 'album.user');

        if ($filters['vocal'] !== null) {
            $query->whereIsVocal($filters['vocal'] === 'yes');
        }

        if (! empty($filters['genres'])) {
            $query->whereIn('genre_id', $filters['genres']);
        }

        if (! empty($filters['types'])) {
            $query->whereIn('track_type_id', $filters['types']);
        }

        if ($filters['archive'] !== null) {
            $query->where('source', $filters['archive']);
        }

        if (! empty($filters['songs'])) {
            // DISTINCT avoids duplicates when a track has several show songs.
            $query->distinct();
            $query->join('show_song_track', 'tracks.id', '=', 'show_song_track.track_id');
            $query->whereIn('show_song_track.show_song_id', $filters['songs']);
        }

        $totalCount = $query->count();

        if ($random) {
            $query->inRandomOrder();
        } elseif ($filters['sort'] === 'trending') {
            // Weighted engagement over the last 24 hours — the same formula
            // as Track::popular on the home page (views 0.1, plays 1,
            // downloads 2). Tracks with no recent activity fall back to
            // newest-first below the trending ones.
            $query->leftJoin(\DB::raw('(
                SELECT track_id,
                       SUM(CASE log_type WHEN 1 THEN 0.1 WHEN 3 THEN 1 WHEN 2 THEN 2 ELSE 0 END) AS weight
                FROM resource_log_items
                WHERE track_id IS NOT NULL AND created_at > now() - INTERVAL \'1\' DAY
                GROUP BY track_id
            ) trending'), 'tracks.id', '=', 'trending.track_id');
            // The alias must go into the select via addSelect: get($columns)
            // silently ignores its argument once a select list exists (and
            // summary() always sets one). Ordering by the output alias keeps
            // DISTINCT (the show-song filter) happy — Postgres requires
            // ORDER BY expressions to appear in the select list.
            $query->addSelect(\DB::raw('COALESCE(trending.weight, 0) AS trending_weight'));
            $query->orderByRaw('trending_weight DESC');
            $query->orderBy('published_at', 'desc');
        } else {
            [$column, $direction] = self::SORTS[$filters['sort']];
            $query->orderBy($column, $direction);
        }

        $query->take(self::PER_PAGE)->skip(self::PER_PAGE * ($page - 1));

        $tracks = [];
        foreach ($query->get() as $track) {
            $tracks[] = Track::mapPublicTrackSummary($track);
        }

        return [
            'tracks' => $tracks,
            'current_page' => $page,
            'total_pages' => (int) ceil($totalCount / self::PER_PAGE),
        ];
    }
}
