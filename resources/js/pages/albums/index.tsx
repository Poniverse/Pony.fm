import React from 'react';
import { Head, router } from '@inertiajs/react';
import { LayoutGrid } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';
import { CollectionCard } from '@/design-system/music/CollectionCard';
import { Pagination } from '@/design-system/navigation/Pagination';
import { EmptyState } from '@/design-system/feedback/EmptyState';
import type { AlbumSummary } from '@/lib/types';

interface AlbumsIndexProps {
    albums: AlbumSummary[];
    currentPage: number;
    totalPages: number;
}

export default function AlbumsIndexPage({ albums, currentPage, totalPages }: AlbumsIndexProps) {
    const setPage = (page: number) => router.get('/albums', page > 1 ? { page } : {}, { preserveState: true });

    return (
        <div className="grid max-w-(--content-max) gap-4 px-7 pt-6 pb-12">
            <Head title="Albums" />
            <SectionHeader title="Albums" />
            {totalPages > 1 ? <Pagination page={currentPage} pages={totalPages} onChange={setPage} /> : null}
            {albums.length ? (
                <div className="grid grid-cols-[repeat(auto-fill,minmax(290px,1fr))] gap-2.5">
                    {albums.map((a) => (
                        <CollectionCard key={a.id} title={a.title} subtitle={'by ' + a.user.name} cover={a.covers.small}
                            count={a.track_count} href={a.url} />
                    ))}
                </div>
            ) : (
                <EmptyState icon={LayoutGrid} title="No albums here yet" />
            )}
            {totalPages > 1 ? <Pagination page={currentPage} pages={totalPages} onChange={setPage} /> : null}
        </div>
    );
}

AlbumsIndexPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
