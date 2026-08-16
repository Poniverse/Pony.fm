import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { List, Lock, Pencil, Plus, Trash2 } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { AccountHeader } from '@/components/AccountHeader';
import { PlaylistDialog } from '@/components/PlaylistDialog';
import { Badge } from '@/design-system/core/Badge';
import { Button } from '@/design-system/core/Button';
import { Dialog } from '@/design-system/feedback/Dialog';
import { EmptyState } from '@/design-system/feedback/EmptyState';
import { api } from '@/lib/api';
import { emitPlaylistsChanged } from '@/lib/events';
import type { PlaylistSummary } from '@/lib/types';

interface OwnedPlaylist extends PlaylistSummary {
    description?: string;
    is_pinned?: boolean;
}

interface AccountPlaylistsProps {
    accountSlug: string;
    playlists: OwnedPlaylist[];
}

export default function AccountPlaylistsPage({ accountSlug, playlists }: AccountPlaylistsProps) {
    const [editing, setEditing] = React.useState<OwnedPlaylist | null>(null);
    const [creating, setCreating] = React.useState(false);
    const [deleting, setDeleting] = React.useState<OwnedPlaylist | null>(null);

    const destroy = (p: OwnedPlaylist) => {
        void api.post('/playlists/delete/' + p.id).then(() => {
            emitPlaylistsChanged();
            setDeleting(null);
            router.reload();
        });
    };

    return (
        <div className="grid max-w-[860px] gap-5 px-7 pt-6 pb-12">
            <Head title="Your playlists" />
            <AccountHeader slug={accountSlug} active="playlists" />
            <div>
                <Button icon={Plus} onClick={() => setCreating(true)}>New playlist</Button>
            </div>
            {playlists.length === 0 ? (
                <EmptyState icon={List} title="No playlists yet">
                    Make one and pin it to your sidebar for quick listening.
                </EmptyState>
            ) : (
                <div className="grid gap-0.5">
                    {playlists.map((p) => (
                        <div key={p.id}
                            className="flex items-center gap-3 rounded-sm px-2.5 py-2">
                            <Link href={p.url} className="grid min-w-0 flex-1 cursor-pointer gap-0.5 no-underline">
                                <span className="truncate text-sm font-semibold text-heading">{p.title}</span>
                                <span className="text-2xs text-faint">{p.track_count} tracks</span>
                            </Link>
                            {!p.is_public ? <Badge tone="warning" icon={Lock}>Private</Badge> : null}
                            {p.is_pinned ? <Badge tone="brand">Pinned</Badge> : null}
                            <Button size="sm" variant="secondary" icon={Pencil} onClick={() => setEditing(p)}>Edit</Button>
                            <Button size="sm" variant="ghost" icon={Trash2} onClick={() => setDeleting(p)} />
                        </div>
                    ))}
                </div>
            )}

            <PlaylistDialog open={creating} onClose={() => setCreating(false)} onSaved={() => router.reload()} />
            <PlaylistDialog open={editing != null} onClose={() => setEditing(null)} onSaved={() => router.reload()}
                playlist={editing ? {
                    id: editing.id,
                    title: editing.title,
                    description: editing.description ?? '',
                    is_public: editing.is_public,
                    is_pinned: !!editing.is_pinned,
                } : null} />
            <Dialog open={deleting != null} title="Delete this playlist?" onClose={() => setDeleting(null)}
                footer={<>
                    <Button variant="secondary" onClick={() => setDeleting(null)}>Keep it</Button>
                    <Button variant="danger" icon={Trash2} onClick={() => deleting && destroy(deleting)}>Delete forever</Button>
                </>}>
                {deleting ? '"' + deleting.title + '" will be gone for good. The tracks in it are unaffected.' : null}
            </Dialog>
        </div>
    );
}

AccountPlaylistsPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
