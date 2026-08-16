import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';
import { Pagination } from '@/design-system/navigation/Pagination';
import { Avatar } from '@/design-system/core/Avatar';
import { Badge } from '@/design-system/core/Badge';
import { timeAgo } from '@/lib/format';
import type { UserSummary } from '@/lib/types';

interface ArtistsIndexProps {
    artists: UserSummary[];
    currentPage: number;
    totalPages: number;
}

export default function ArtistsIndexPage({ artists, currentPage, totalPages }: ArtistsIndexProps) {
    const setPage = (page: number) => router.get('/artists', page > 1 ? { page } : {}, { preserveState: true });

    return (
        <div className="grid max-w-(--content-max) gap-4 px-7 pt-6 pb-12">
            <Head title="Artists" />
            <SectionHeader title="Artists" />
            {totalPages > 1 ? <Pagination page={currentPage} pages={totalPages} onChange={setPage} /> : null}
            <div className="grid grid-cols-[repeat(auto-fill,minmax(270px,1fr))] gap-2.5">
                {artists.map((a) => (
                    <Link key={a.id} href={a.url}
                        className="flex cursor-pointer items-center gap-3 rounded-card border border-border-subtle bg-card p-2.5 no-underline transition-[background,color,border-color] duration-(--dur-fast) ease-(--ease-standard) hover:bg-surface-3">
                        <Avatar src={a.avatars.small} name={a.name} size="lg" />
                        <span className="grid min-w-0 flex-1 gap-[3px]">
                            <span className="truncate font-display text-lg font-semibold leading-(--leading-normal) text-heading">{a.name}</span>
                            <span className="text-xs text-faint">Joined {timeAgo(a.created_at)}</span>
                        </span>
                        {a.is_archived ? <Badge tone="warning">Archived</Badge> : null}
                    </Link>
                ))}
            </div>
            {totalPages > 1 ? <Pagination page={currentPage} pages={totalPages} onChange={setPage} /> : null}
        </div>
    );
}

ArtistsIndexPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
