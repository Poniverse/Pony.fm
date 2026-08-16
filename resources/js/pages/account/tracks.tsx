import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Check, Music, Pencil, Upload } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { AccountHeader } from '@/components/AccountHeader';
import { TrackEditor } from '@/components/TrackEditor';
import { Badge } from '@/design-system/core/Badge';
import { Button } from '@/design-system/core/Button';
import { EmptyState } from '@/design-system/feedback/EmptyState';
import { timeAgo } from '@/lib/format';

export interface PrivateTrackSummary {
    id: number;
    title: string;
    slug: string;
    is_vocal: boolean;
    is_explicit: boolean;
    is_downloadable: boolean;
    is_published: boolean;
    is_listed: boolean;
    created_at: string;
    published_at: string | null;
    duration: string;
    cover_url: string;
}

interface AccountTracksProps {
    accountSlug: string;
    tracks: PrivateTrackSummary[];
    editId: number | null;
}

export default function AccountTracksPage({ accountSlug, tracks, editId }: AccountTracksProps) {
    const base = '/' + accountSlug + '/account/tracks';

    if (editId != null) {
        return (
            <div className="grid max-w-[860px] gap-5 px-7 pt-6 pb-12">
                <Head title="Edit track" />
                <AccountHeader slug={accountSlug} active="tracks" />
                <TrackEditor trackId={editId} onSaved={() => router.visit(base)} onDeleted={() => router.visit(base)} />
            </div>
        );
    }

    return (
        <div className="grid max-w-[860px] gap-5 px-7 pt-6 pb-12">
            <Head title="Your tracks" />
            <AccountHeader slug={accountSlug} active="tracks" />
            {tracks.length === 0 ? (
                <EmptyState icon={Music} title="No tracks yet"
                    action={<Button render={<Link href={'/' + accountSlug + '/account/uploader'} />} size="sm" icon={Upload}>Upload something</Button>}>
                    Everything you upload shows up here — published or not.
                </EmptyState>
            ) : (
                <div className="grid gap-0.5">
                    {tracks.map((t) => (
                        <div key={t.id}
                            className="flex items-center gap-3 rounded-sm px-2.5 py-2 transition-[background] duration-(--dur-fast) ease-(--ease-standard) hover:bg-surface-hover">
                            <Link href={base + '/edit/' + t.id} className="flex min-w-0 flex-1 items-center gap-3 no-underline">
                                <img src={t.cover_url} alt="" className="h-10 w-10 flex-none rounded-art" />
                                <span className="grid min-w-0 flex-1 gap-0.5">
                                    <span className="truncate text-sm font-semibold text-heading">{t.title}</span>
                                    <span className="text-2xs text-faint">
                                        {t.is_published ? 'Published ' + timeAgo(t.published_at) : 'Uploaded ' + timeAgo(t.created_at)}
                                    </span>
                                </span>
                            </Link>
                            {!t.is_published ? <Badge tone="warning">Unpublished</Badge> : null}
                            {t.is_published ? (
                                t.is_listed
                                    ? <Badge tone="lossless" icon={Check}>Listed</Badge>
                                    : <Badge tone="neutral">Unlisted</Badge>
                            ) : null}
                            {t.is_explicit ? <Badge tone="danger">Explicit</Badge> : null}
                            <Button render={<Link href={base + '/edit/' + t.id} />} size="sm" variant="secondary" icon={Pencil}>Edit</Button>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

AccountTracksPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
