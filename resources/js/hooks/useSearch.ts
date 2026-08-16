import { useEffect, useRef, useState } from 'react';
import { api } from '@/lib/api';
import type { AlbumSummary, PlaylistSummary, TrackSummary, UserSummary } from '@/lib/types';

export interface SearchResultsData {
    tracks: TrackSummary[];
    users: UserSummary[];
    albums: AlbumSummary[];
    playlists: PlaylistSummary[];
}

/** Sidebar universal search: 300ms debounce, 3-character minimum — same
 *  behaviour as the old pfm-search directive. `results` is null until a
 *  long-enough query has answered, so the panel only opens with real data. */
export function useSearch() {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchResultsData | null>(null);
    const timer = useRef<ReturnType<typeof setTimeout>>(undefined);
    const requestId = useRef(0);

    useEffect(() => {
        clearTimeout(timer.current);
        if (query.trim().length < 3) {
            setResults(null);
            return;
        }
        const id = ++requestId.current;
        timer.current = setTimeout(() => {
            api.get<{ results: Partial<SearchResultsData> }>('/search', { params: { query } })
                .then(({ data }) => {
                    if (id !== requestId.current) return;
                    const r = data.results;
                    setResults({
                        tracks: r.tracks ?? [],
                        users: r.users ?? [],
                        albums: r.albums ?? [],
                        playlists: r.playlists ?? [],
                    });
                })
                .catch(() => setResults(null));
        }, 300);
        return () => clearTimeout(timer.current);
    }, [query]);

    return { query, setQuery, results, clear: () => { setQuery(''); setResults(null); } };
}
