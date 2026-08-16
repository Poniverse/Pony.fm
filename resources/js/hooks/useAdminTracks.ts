import { useCallback, useEffect, useState } from 'react';
import { api } from '@/lib/api';
import type { TrackSummary } from '@/lib/types';
import type { TrackFilterState } from '@/lib/filters';

const ORDER: Record<string, string> = {
    '': 'published_at,desc',
    plays: 'play_count,desc',
    downloads: 'download_count,desc',
    favourites: 'favourite_count,desc',
    alphabetical: 'title,asc',
};

/** Paged track listings for the admin area (all tracks / classifier queue). */
export function useAdminTracks(endpoint: '/admin/tracks' | '/admin/tracks/unclassified', filters: TrackFilterState, page: number) {
    const [tracks, setTracks] = useState<TrackSummary[]>([]);
    const [totalPages, setTotalPages] = useState(1);
    const [loading, setLoading] = useState(true);
    const [generation, setGeneration] = useState(0);

    const refresh = useCallback(() => setGeneration((g) => g + 1), []);

    useEffect(() => {
        let live = true;
        setLoading(true);
        const params = new URLSearchParams();
        params.set('page', String(page));
        params.set('order', ORDER[filters.sort] ?? ORDER['']);
        if (filters.vocal) params.set('is_vocal', filters.vocal === 'yes' ? 'true' : 'false');
        filters.genres.forEach((id) => params.append('genres[]', String(id)));
        filters.types.forEach((id) => params.append('types[]', String(id)));
        filters.songs.forEach((id) => params.append('songs[]', String(id)));

        api.get<{ tracks: TrackSummary[]; total_pages: number }>(endpoint + '?' + params.toString())
            .then(({ data }) => {
                if (!live) return;
                setTracks(data.tracks);
                setTotalPages(data.total_pages || 1);
            })
            .catch(() => live && setTracks([]))
            .finally(() => live && setLoading(false));
        return () => { live = false; };
    }, [endpoint, filters, page, generation]);

    return { tracks, totalPages, loading, refresh };
}
