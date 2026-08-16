import React from 'react';
import { router, usePage } from '@inertiajs/react';
import { Lock, Pencil, Trash2 } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/design-system/core/Button';
import { Badge } from '@/design-system/core/Badge';
import { Dialog } from '@/design-system/feedback/Dialog';
import { CollectionShow } from '@/components/CollectionShow';
import { PlaylistDialog } from '@/components/PlaylistDialog';
import { api } from '@/lib/api';
import { emitPlaylistsChanged } from '@/lib/events';
import type { PlaylistShow, SharedProps } from '@/lib/types';

export default function PlaylistShowPage({ playlist }: { playlist: PlaylistShow }) {
    const { auth } = usePage<SharedProps>().props;
    const isOwner = auth.user?.id === playlist.user.id;
    const [editing, setEditing] = React.useState(false);
    const [deleting, setDeleting] = React.useState(false);

    const destroy = () => {
        void api.post('/playlists/delete/' + playlist.id).then(() => {
            emitPlaylistsChanged();
            router.visit('/playlists');
        });
    };

    return (
        <>
            <CollectionShow
                kind="playlist"
                data={playlist}
                extraBadges={!playlist.is_public ? <Badge tone="warning" icon={Lock}>Private</Badge> : null}
                extraActions={isOwner ? (
                    <>
                        <Button variant="ghost" icon={Pencil} onClick={() => setEditing(true)}>Edit</Button>
                        <Button variant="ghost" icon={Trash2} onClick={() => setDeleting(true)}>Delete</Button>
                    </>
                ) : null}
            />
            <PlaylistDialog open={editing} onClose={() => setEditing(false)}
                playlist={{
                    id: playlist.id,
                    title: playlist.title,
                    description: playlist.description ?? '',
                    is_public: playlist.is_public,
                    is_pinned: !!playlist.user_data?.is_pinned,
                }}
                onSaved={() => router.reload()} />
            <Dialog open={deleting} title="Delete this playlist?" onClose={() => setDeleting(false)}
                footer={<>
                    <Button variant="secondary" onClick={() => setDeleting(false)}>Keep it</Button>
                    <Button variant="danger" icon={Trash2} onClick={destroy}>Delete forever</Button>
                </>}>
                "{playlist.title}" will be gone for good. The tracks in it are unaffected.
            </Dialog>
        </>
    );
}

PlaylistShowPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
