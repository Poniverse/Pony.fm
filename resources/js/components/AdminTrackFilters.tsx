import React from 'react';
import { FilterBar, type FilterSpec } from '@/design-system/navigation/FilterBar';
import { Select } from '@/design-system/core/Select';
import { useTaxonomies } from '@/hooks/useTaxonomies';
import { SORT_OPTIONS, toggleId, type SortKey, type TrackFilterState } from '@/lib/filters';

/** Local-state variant of the browse filters, used by the admin listings. */
export function AdminTrackFilters({ filters, onChange }: {
    filters: TrackFilterState;
    onChange: (next: TrackFilterState) => void;
}) {
    const taxonomies = useTaxonomies();

    const genres = taxonomies?.genres ?? [];
    const types = taxonomies?.track_types ?? [];

    const specs: FilterSpec[] = [
        { id: 'genres', label: 'Genre', options: genres.map((g) => g.name), selected: genres.filter((g) => filters.genres.includes(g.id)).map((g) => g.name) },
        { id: 'types', label: 'Type', options: types.map((t) => t.title), selected: types.filter((t) => filters.types.includes(t.id)).map((t) => t.title) },
        { id: 'vocal', label: 'Vocals', options: ['Yes', 'No'], selected: filters.vocal ? [filters.vocal === 'yes' ? 'Yes' : 'No'] : [] },
    ];

    const onToggle = (filterId: string, option: string) => {
        const next = { ...filters };
        if (filterId === 'genres') { const g = genres.find((x) => x.name === option); if (g) next.genres = toggleId(filters.genres, g.id); }
        else if (filterId === 'types') { const t = types.find((x) => x.title === option); if (t) next.types = toggleId(filters.types, t.id); }
        else if (filterId === 'vocal') { const v = option === 'Yes' ? 'yes' : 'no'; next.vocal = filters.vocal === v ? null : v; }
        onChange(next);
    };

    const onClear = (filterId: string) => {
        const next = { ...filters };
        if (filterId === 'genres') next.genres = [];
        else if (filterId === 'types') next.types = [];
        else if (filterId === 'vocal') next.vocal = null;
        onChange(next);
    };

    return (
        <FilterBar filters={specs} onToggle={onToggle} onClear={onClear}
            right={
                <div className="w-[190px]">
                    <Select options={SORT_OPTIONS.map((s) => ({ value: s.value, label: 'Sort: ' + s.label }))}
                        value={filters.sort}
                        onChange={(e) => onChange({ ...filters, sort: e.target.value as SortKey })} />
                </div>
            } />
    );
}
