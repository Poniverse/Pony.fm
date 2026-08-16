import { useEffect, useRef, useState } from 'react';
import { api } from '@/lib/api';

export interface SearchHit {
    title: string;
    kind: 'track' | 'album' | 'playlist' | 'artist';
    url: string;
}

interface RawResults {
    tracks?: { title: string; url: string }[];
    albums?: { title: string; url: string }[];
    playlists?: { title: string; url: string }[];
    users?: { name: string; url: string }[];
}

/** Sidebar universal search: 300ms debounce, 3-character minimum — same
 *  behaviour as the old pfm-search directive. */
export function useSearch() {
    const [query, setQuery] = useState('');
    const [hits, setHits] = useState<SearchHit[]>([]);
    const timer = useRef<ReturnType<typeof setTimeout>>(undefined);
    const requestId = useRef(0);

    useEffect(() => {
        clearTimeout(timer.current);
        if (query.trim().length < 3) {
            setHits([]);
            return;
        }
        const id = ++requestId.current;
        timer.current = setTimeout(() => {
            api.get<{ results: RawResults }>('/search', { params: { query } })
                .then(({ data }) => {
                    if (id !== requestId.current) return;
                    const r = data.results;
                    setHits([
                        ...(r.tracks ?? []).map((t): SearchHit => ({ title: t.title, kind: 'track', url: t.url })),
                        ...(r.users ?? []).map((u): SearchHit => ({ title: u.name, kind: 'artist', url: u.url })),
                        ...(r.albums ?? []).map((a): SearchHit => ({ title: a.title, kind: 'album', url: a.url })),
                        ...(r.playlists ?? []).map((p): SearchHit => ({ title: p.title, kind: 'playlist', url: p.url })),
                    ]);
                })
                .catch(() => setHits([]));
        }, 300);
        return () => clearTimeout(timer.current);
    }, [query]);

    return { query, setQuery, hits, clear: () => { setQuery(''); setHits([]); } };
}
