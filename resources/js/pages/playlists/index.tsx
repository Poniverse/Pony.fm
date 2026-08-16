import React from 'react';
import { Head, router } from '@inertiajs/react';
import { List } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';
import { CollectionCard } from '@/design-system/music/CollectionCard';
import { Pagination } from '@/design-system/navigation/Pagination';
import { Select } from '@/design-system/core/Select';
import { EmptyState } from '@/design-system/feedback/EmptyState';
import type { PlaylistSummary } from '@/lib/types';

const SORTS = [
    { value: 'favourites', label: 'Sort: Most Favourited' },
    { value: 'plays', label: 'Sort: Most Viewed' },
    { value: 'downloads', label: 'Sort: Most Downloaded' },
    { value: 'alphabetical', label: 'Sort: Alphabetical' },
    { value: 'latest', label: 'Sort: Latest' },
    { value: 'tracks', label: 'Sort: Track count' },
];

interface PlaylistsIndexProps {
    playlists: PlaylistSummary[];
    currentPage: number;
    totalPages: number;
    sort: string;
}

export default function PlaylistsIndexPage({ playlists, currentPage, totalPages, sort }: PlaylistsIndexProps) {
    const navigate = (nextSort: string, page = 1) =>
        router.get('/playlists', {
            ...(nextSort !== 'favourites' ? { filter: 'sort-' + nextSort } : {}),
            ...(page > 1 ? { page } : {}),
        }, { preserveState: true });

    return (
        <div className="grid max-w-(--content-max) gap-4 px-7 pt-6 pb-12">
            <Head title="Playlists" />
            <SectionHeader title="Playlists" action={
                <div className="w-[210px]">
                    <Select options={SORTS} value={sort} onChange={(e) => navigate(e.target.value)} />
                </div>
            } />
            {totalPages > 1 ? <Pagination page={currentPage} pages={totalPages} onChange={(p) => navigate(sort, p)} /> : null}
            {playlists.length ? (
                <div className="grid grid-cols-[repeat(auto-fill,minmax(290px,1fr))] gap-2.5">
                    {playlists.map((p) => (
                        <CollectionCard key={p.id} title={p.title} subtitle={'by ' + p.user.name} cover={p.covers.small}
                            count={p.track_count} href={p.url} />
                    ))}
                </div>
            ) : (
                <EmptyState icon={List} title="No playlists here yet" />
            )}
            {totalPages > 1 ? <Pagination page={currentPage} pages={totalPages} onChange={(p) => navigate(sort, p)} /> : null}
        </div>
    );
}

PlaylistsIndexPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
