import React from 'react';
import { usePage } from '@inertiajs/react';
import { Check, List, Plus } from 'lucide-react';
import { Button } from '@/design-system/core/Button';
import { IconButton } from '@/design-system/core/IconButton';
import { Popover } from '@/design-system/feedback/Popover';
import { PlaylistDialog } from '@/components/PlaylistDialog';
import { api } from '@/lib/api';
import { emitPlaylistsChanged } from '@/lib/events';
import type { SharedProps } from '@/lib/types';
import { cn } from '@/lib/utils';

interface OwnedPlaylist {
    id: number;
    title: string;
    track_ids?: number[];
}

/** "Add to playlist" popover: the user's playlists + a new-playlist flow. */
export function AddToPlaylist({ trackId, iconOnly, iconRound, iconSize }: {
    trackId: number;
    iconOnly?: boolean;
    /** Icon-only styling: round shape and size, e.g. to match a round play button. */
    iconRound?: boolean;
    iconSize?: 'sm' | 'md' | 'lg';
}) {
    const { auth } = usePage<SharedProps>().props;
    const [open, setOpen] = React.useState(false);
    const [creating, setCreating] = React.useState(false);
    const [playlists, setPlaylists] = React.useState<OwnedPlaylist[] | null>(null);
    const [addedTo, setAddedTo] = React.useState<Record<number, boolean>>({});

    if (!auth.user) return null;
    const user = auth.user;

    const load = () => {
        api.get<OwnedPlaylist[]>('/users/' + user.id + '/playlists')
            .then(({ data }) => setPlaylists(data))
            .catch(() => setPlaylists([]));
    };

    const toggleOpen = () => {
        setOpen((v) => {
            if (!v && playlists === null) load();
            return !v;
        });
    };

    const add = (playlist: OwnedPlaylist) => {
        setAddedTo((m) => ({ ...m, [playlist.id]: true }));
        api.post('/playlists/' + playlist.id + '/add-track', { track_id: trackId })
            .then(() => emitPlaylistsChanged())
            .catch(() => setAddedTo((m) => ({ ...m, [playlist.id]: false })));
    };

    return (
        <span className="relative inline-flex">
            {iconOnly ? (
                <IconButton icon={Plus} label="Add to playlist" round={iconRound} size={iconSize} onClick={toggleOpen} active={open} />
            ) : (
                <Button variant="ghost" icon={Plus} onClick={toggleOpen} active={open}>Add to playlist</Button>
            )}
            <Popover open={open} title="Add to playlist" placement="below" width={280} onClose={() => setOpen(false)}
                footer={<a href="#new-playlist" onClick={(e) => { e.preventDefault(); setOpen(false); setCreating(true); }}>+ New playlist</a>}>
                <div className="grid gap-0.5">
                    {playlists === null ? (
                        <span className="px-2 py-3 text-sm text-muted-foreground">Loading…</span>
                    ) : playlists.length === 0 ? (
                        <span className="px-2 py-3 text-sm text-muted-foreground">You have no playlists yet.</span>
                    ) : playlists.map((p) => (
                        <button key={p.id} type="button" disabled={!!addedTo[p.id]} onClick={() => add(p)}
                            className={cn(
                                'flex w-full items-center gap-2 rounded-sm border-none bg-transparent px-2.5 py-2 text-left font-text text-sm',
                                addedTo[p.id] ? 'cursor-default text-faint' : 'cursor-pointer text-foreground hover:bg-surface-hover',
                            )}>
                            {addedTo[p.id]
                                ? <Check className="size-4 flex-none text-faint" aria-hidden="true" />
                                : <List className="size-4 flex-none text-faint" aria-hidden="true" />}
                            <span className="min-w-0 flex-1 truncate">{p.title}</span>
                        </button>
                    ))}
                </div>
            </Popover>
            <PlaylistDialog open={creating} onClose={() => setCreating(false)}
                onSaved={(p) => { setPlaylists(null); add({ id: p.id, title: p.title }); }} />
        </span>
    );
}
