import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { LayoutGrid, Plus } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { AccountHeader } from '@/components/AccountHeader';
import { AlbumEditor } from '@/components/AlbumEditor';
import { Button } from '@/design-system/core/Button';
import { EmptyState } from '@/design-system/feedback/EmptyState';
import { CollectionCard } from '@/design-system/music/CollectionCard';

interface OwnedAlbum {
    id: number;
    title: string;
    slug: string;
    url: string;
    created_at: string;
    cover_url: string;
    track_count: number;
}

interface AccountAlbumsProps {
    accountSlug: string;
    albums: OwnedAlbum[];
    editId: number | null;
    creating: boolean;
}

export default function AccountAlbumsPage({ accountSlug, albums, editId, creating }: AccountAlbumsProps) {
    const base = '/' + accountSlug + '/account/albums';

    if (editId != null || creating) {
        return (
            <div className="grid max-w-[860px] gap-5 px-7 pt-6 pb-12">
                <Head title={creating ? 'New album' : 'Edit album'} />
                <AccountHeader slug={accountSlug} active="albums" />
                <AlbumEditor albumId={editId} onSaved={() => router.visit(base)} onDeleted={() => router.visit(base)} />
            </div>
        );
    }

    return (
        <div className="grid max-w-[860px] gap-5 px-7 pt-6 pb-12">
            <Head title="Your albums" />
            <AccountHeader slug={accountSlug} active="albums" />
            <div>
                <Button render={<Link href={base + '/create'} />} icon={Plus}>New album</Button>
            </div>
            {albums.length === 0 ? (
                <EmptyState icon={LayoutGrid} title="No albums yet">
                    Group your tracks into an album and it'll show up here.
                </EmptyState>
            ) : (
                <div className="grid grid-cols-[repeat(auto-fill,minmax(290px,1fr))] gap-2.5">
                    {albums.map((a) => (
                        <CollectionCard key={a.id} title={a.title} subtitle={a.track_count + ' tracks'} cover={a.cover_url}
                            href={base + '/edit/' + a.id} />
                    ))}
                </div>
            )}
        </div>
    );
}

AccountAlbumsPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
