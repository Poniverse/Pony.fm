import React from 'react';
import { Head } from '@inertiajs/react';
import { ListFilter } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { AdminHeader } from '@/components/AdminHeader';
import { AdminTrackFilters } from '@/components/AdminTrackFilters';
import { TrackList } from '@/components/TrackList';
import { Pagination } from '@/design-system/navigation/Pagination';
import { EmptyState } from '@/design-system/feedback/EmptyState';
import { useAdminTracks } from '@/hooks/useAdminTracks';
import { DEFAULT_FILTERS, type TrackFilterState } from '@/lib/filters';

export default function AdminTracksPage() {
    const [filters, setFilters] = React.useState<TrackFilterState>(DEFAULT_FILTERS);
    const [page, setPage] = React.useState(1);
    const { tracks, totalPages, loading } = useAdminTracks('/admin/tracks', filters, page);

    const changeFilters = (next: TrackFilterState) => { setFilters(next); setPage(1); };

    return (
        <div className="grid max-w-(--content-max) gap-4 px-7 pt-6 pb-12">
            <Head title="Admin - Tracks" />
            <AdminHeader active="tracks" />
            <AdminTrackFilters filters={filters} onChange={changeFilters} />
            {totalPages > 1 ? <Pagination page={page} pages={totalPages} onChange={setPage} /> : null}
            {loading ? (
                <p className="m-0 text-sm text-muted-foreground">Loading…</p>
            ) : tracks.length ? (
                <TrackList tracks={tracks} />
            ) : (
                <EmptyState icon={ListFilter} title="No tracks match" />
            )}
            {totalPages > 1 ? <Pagination page={page} pages={totalPages} onChange={setPage} /> : null}
        </div>
    );
}

AdminTracksPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
