import { useEffect, useState } from 'react';
import { api } from '@/lib/api';

export interface Taxonomies {
    genres: { id: number; name: string; slug: string; track_count: number }[];
    track_types: { id: number; title: string; track_count: number }[];
    show_songs: { id: number; title: string; slug: string; track_count: number }[];
    licenses: { id: number; title: string; description: string; affiliate_distribution: boolean; open_distribution: boolean; remix: boolean }[];
}

// One fetch per browser session — the payload is server-cached anyway.
let cache: Promise<Taxonomies> | null = null;

export function useTaxonomies(): Taxonomies | null {
    const [taxonomies, setTaxonomies] = useState<Taxonomies | null>(null);

    useEffect(() => {
        cache ??= api.get<Taxonomies>('/taxonomies/all').then(({ data }) => data);
        let mounted = true;
        cache.then((t) => { if (mounted) setTaxonomies(t); });
        return () => { mounted = false; };
    }, []);

    return taxonomies;
}
