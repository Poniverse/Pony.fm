/**
 * The `vocal-yes!genres-3-7!sort-plays` filter grammar used in shareable
 * /tracks URLs. Mirrors app/Library/TrackFilters.php — keep the two in sync.
 */

export type SortKey = '' | 'trending' | 'plays' | 'downloads' | 'favourites' | 'alphabetical';
export type ArchiveKey = 'eqbeats' | 'mlpma' | 'ponify';

export interface TrackFilterState {
    vocal: 'yes' | 'no' | null;
    sort: SortKey;
    genres: number[];
    types: number[];
    songs: number[];
    archive: ArchiveKey | null;
}

export const DEFAULT_FILTERS: TrackFilterState = {
    vocal: null,
    sort: '',
    genres: [],
    types: [],
    songs: [],
    archive: null,
};

export const SORT_OPTIONS: { value: SortKey; label: string }[] = [
    { value: '', label: 'Latest' },
    { value: 'trending', label: 'Popular Today' },
    { value: 'plays', label: 'Most Played' },
    { value: 'downloads', label: 'Most Downloaded' },
    { value: 'favourites', label: 'Most Favourited' },
    { value: 'alphabetical', label: 'Alphabetical' },
];

export const ARCHIVE_OPTIONS: { value: ArchiveKey | ''; label: string }[] = [
    { value: '', label: 'None' },
    { value: 'eqbeats', label: 'Equestrian Beats' },
    { value: 'mlpma', label: 'MLP Music Archive' },
    { value: 'ponify', label: 'Ponify' },
];

/** Serialise to the URL segment; empty string means "all defaults". */
export function serializeFilters(f: TrackFilterState): string {
    const parts: string[] = [];
    if (f.vocal) parts.push('vocal-' + f.vocal);
    if (f.sort) parts.push('sort-' + f.sort);
    if (f.genres.length) parts.push('genres-' + f.genres.join('-'));
    if (f.types.length) parts.push('types-' + f.types.join('-'));
    if (f.songs.length) parts.push('songs-' + f.songs.join('-'));
    if (f.archive) parts.push('archive-' + f.archive);
    return parts.join('!');
}

export function toggleId(list: number[], id: number): number[] {
    return list.includes(id) ? list.filter((x) => x !== id) : [...list, id];
}
