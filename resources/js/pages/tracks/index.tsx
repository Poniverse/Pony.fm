import React from 'react';
import { Head, router } from '@inertiajs/react';
import { ListFilter } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';
import { FilterBar, type FilterSpec } from '@/design-system/navigation/FilterBar';
import { Pagination } from '@/design-system/navigation/Pagination';
import { Select } from '@/design-system/core/Select';
import { EmptyState } from '@/design-system/feedback/EmptyState';
import { Button } from '@/design-system/core/Button';
import { TrackList } from '@/components/TrackList';
import { useTaxonomies } from '@/hooks/useTaxonomies';
import {
    ARCHIVE_OPTIONS, DEFAULT_FILTERS, SORT_OPTIONS, serializeFilters, toggleId,
    type ArchiveKey, type SortKey, type TrackFilterState,
} from '@/lib/filters';
import type { TrackSummary } from '@/lib/types';

interface TracksIndexProps {
    tracks: TrackSummary[];
    currentPage: number;
    totalPages: number;
    filters: TrackFilterState;
    mode: 'all' | 'popular' | 'random';
}

export default function TracksIndexPage({ tracks, currentPage, totalPages, filters, mode }: TracksIndexProps) {
    const taxonomies = useTaxonomies();
    const [jump, setJump] = React.useState('');

    // Optimistic copy of the filters: selections mark themselves instantly
    // while the visit fetches the filtered data; server props re-sync it
    // when the response lands (and are the source of truth on conflict).
    const [local, setLocal] = React.useState(filters);
    React.useEffect(() => setLocal(filters), [filters]);

    const apply = (next: TrackFilterState, page = 1) => {
        setLocal(next);
        const filter = serializeFilters(next);
        router.get('/tracks', {
            ...(filter ? { filter } : {}),
            ...(page > 1 ? { page } : {}),
        }, { preserveState: true, preserveScroll: false });
    };

    const setPage = (page: number) => apply(local, page);

    // Options come from the taxonomies endpoint; entries with no tracks are
    // hidden, matching the old filter dropdowns.
    const byName = <T extends { track_count: number }>(items: T[] | undefined, label: (t: T) => string, id: (t: T) => number) => {
        const withTracks = (items ?? []).filter((i) => i.track_count > 0);
        return {
            options: withTracks.map(label),
            toId: (name: string) => withTracks.find((i) => label(i) === name),
            fromIds: (ids: number[]) => withTracks.filter((i) => ids.includes(id(i))).map(label),
        };
    };

    const genres = byName(taxonomies?.genres, (g) => g.name, (g) => g.id);
    const types = byName(taxonomies?.track_types, (t) => t.title, (t) => t.id);
    const songs = byName(taxonomies?.show_songs, (s) => s.title, (s) => s.id);

    const filterSpecs: FilterSpec[] = [
        { id: 'genres', label: 'Genre', options: genres.options, selected: genres.fromIds(local.genres) },
        { id: 'types', label: 'Type', options: types.options, selected: types.fromIds(local.types) },
        { id: 'songs', label: 'Show song', options: songs.options, selected: songs.fromIds(local.songs) },
        { id: 'vocal', label: 'Vocals', options: ['Yes', 'No'], selected: local.vocal ? [local.vocal === 'yes' ? 'Yes' : 'No'] : [] },
        { id: 'archive', label: 'Archive', options: ARCHIVE_OPTIONS.filter((a) => a.value).map((a) => a.label), selected: local.archive ? [ARCHIVE_OPTIONS.find((a) => a.value === local.archive)!.label] : [] },
    ];

    const onToggle = (filterId: string, option: string) => {
        const next = { ...local };
        if (filterId === 'genres') { const g = genres.toId(option); if (g) next.genres = toggleId(local.genres, g.id); }
        else if (filterId === 'types') { const t = types.toId(option); if (t) next.types = toggleId(local.types, t.id); }
        else if (filterId === 'songs') { const s = songs.toId(option); if (s) next.songs = toggleId(local.songs, s.id); }
        else if (filterId === 'vocal') { const v = option === 'Yes' ? 'yes' : 'no'; next.vocal = local.vocal === v ? null : v; }
        else if (filterId === 'archive') {
            const a = ARCHIVE_OPTIONS.find((x) => x.label === option)?.value as ArchiveKey | '';
            next.archive = local.archive === a ? null : (a || null);
        }
        apply(next);
    };

    const onClear = (filterId: string) => {
        const next = { ...local };
        if (filterId === 'genres') next.genres = [];
        else if (filterId === 'types') next.types = [];
        else if (filterId === 'songs') next.songs = [];
        else if (filterId === 'vocal') next.vocal = null;
        else if (filterId === 'archive') next.archive = null;
        apply(next);
    };

    const title = mode === 'popular' ? 'Popular tracks' : mode === 'random' ? 'Random tracks' : 'Tracks';

    return (
        <div className="grid max-w-(--content-max) gap-4 px-7 pt-6 pb-12">
            <Head title={title} />
            <SectionHeader title={title} />
            <FilterBar
                filters={filterSpecs}
                onToggle={onToggle}
                onClear={onClear}
                right={
                    <div className="w-[190px]">
                        <Select
                            options={SORT_OPTIONS.map((s) => ({ value: s.value, label: 'Sort: ' + s.label }))}
                            value={local.sort}
                            onChange={(e) => apply({ ...local, sort: e.target.value as SortKey })}
                        />
                    </div>
                }
            />

            {totalPages > 1 ? <Pagination page={currentPage} pages={totalPages} onChange={setPage} /> : null}

            {tracks.length ? (
                <TrackList tracks={tracks} />
            ) : (
                <EmptyState icon={ListFilter} title="Nothing matches those filters"
                    action={<Button size="sm" variant="secondary" onClick={() => apply(DEFAULT_FILTERS)}>Clear filters</Button>}>
                    Try widening the genre or type filters.
                </EmptyState>
            )}

            {totalPages > 1 ? (
                <div className="flex flex-wrap items-center gap-3">
                    <Pagination page={currentPage} pages={totalPages} onChange={setPage} />
                    <form onSubmit={(e) => { e.preventDefault(); const p = parseInt(jump, 10); if (p >= 1 && p <= totalPages) setPage(p); }}
                        className="flex items-center gap-1.5">
                        <span className="text-xs text-faint">Jump to</span>
                        <input value={jump} onChange={(e) => setJump(e.target.value)} inputMode="numeric" aria-label="Jump to page"
                            className="w-[52px] rounded-sm border border-border bg-surface-3 px-2 py-[5px] font-mono text-xs leading-(--leading-normal) text-heading outline-none" />
                        <span className="text-xs text-faint">of {totalPages}</span>
                    </form>
                </div>
            ) : null}
        </div>
    );
}

TracksIndexPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
