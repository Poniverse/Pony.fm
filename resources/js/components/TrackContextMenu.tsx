import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, List, ListEnd, ListStart, Music, Play, Plus, Star, User } from 'lucide-react';
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuSub,
    ContextMenuSubContent,
    ContextMenuSubTrigger,
    ContextMenuTrigger,
} from '@/design-system/primitives/context-menu';
import { PlaylistDialog } from '@/components/PlaylistDialog';
import { usePlayer } from '@/lib/player/PlayerContext';
import { useOwnedPlaylists, type OwnedPlaylist } from '@/hooks/useOwnedPlaylists';
import { api } from '@/lib/api';
import { emitPlaylistsChanged, emitSignInPrompt } from '@/lib/events';
import type { SharedProps, TrackSummary } from '@/lib/types';

/**
 * The right-click menu for a track, shared by list rows, queue rows and the
 * player's now-playing cluster. The trigger renders with display:contents so
 * it never disturbs the wrapped element's layout.
 */
export function TrackContextMenu({ track, playContext, onPlayNow, onRemoveFromQueue, favourited, onFavourite, children }: {
    track: TrackSummary;
    /** List + position used for "Play now" (defaults to a one-track queue). */
    playContext?: { tracks: TrackSummary[]; index: number };
    /** Overrides "Play now" entirely (e.g. queue rows jump by position). */
    onPlayNow?: () => void;
    /** Queue rows only: adds a "Remove from queue" item. */
    onRemoveFromQueue?: () => void;
    /** Controlled favourite state; leave undefined to let the menu manage it. */
    favourited?: boolean;
    onFavourite?: () => void;
    children: React.ReactNode;
}) {
    const player = usePlayer();
    const { auth } = usePage<SharedProps>().props;
    const [menuOpen, setMenuOpen] = React.useState(false);
    const [creatingPlaylist, setCreatingPlaylist] = React.useState(false);
    const ownedPlaylists = useOwnedPlaylists(menuOpen && !!auth.user);
    const [ownFav, setOwnFav] = React.useState(!!track.user_data?.is_favourited);
    React.useEffect(() => setOwnFav(!!track.user_data?.is_favourited), [track.id, track.user_data?.is_favourited]);

    const isFavourited = favourited ?? ownFav;
    const toggleFavourite = onFavourite ?? (() => {
        setOwnFav((f) => !f);
        api.post<{ is_favourited: boolean }>('/favourites/toggle', { type: 'track', id: track.id })
            .then(({ data }) => setOwnFav(data.is_favourited))
            .catch(() => setOwnFav((f) => !f));
    });

    const playNow = onPlayNow ?? (() => {
        if (playContext) player.playTracks(playContext.tracks, playContext.index);
        else player.playTracks([track]);
    });

    const addToPlaylist = (playlist: OwnedPlaylist) => {
        api.post('/playlists/' + playlist.id + '/add-track', { track_id: track.id })
            .then(() => emitPlaylistsChanged())
            .catch(() => undefined);
    };

    return (
        <ContextMenu onOpenChange={setMenuOpen}>
            <ContextMenuTrigger className="contents">{children}</ContextMenuTrigger>
            <ContextMenuContent className="w-52">
                <ContextMenuItem onClick={playNow}>
                    <Play aria-hidden="true" /> Play now
                </ContextMenuItem>
                <ContextMenuItem onClick={() => player.queueNext(track)}>
                    <ListStart aria-hidden="true" /> Play next
                </ContextMenuItem>
                <ContextMenuItem onClick={() => player.queueLast(track)}>
                    <ListEnd aria-hidden="true" /> Add to queue
                </ContextMenuItem>
                {auth.user ? (
                    <ContextMenuSub>
                        <ContextMenuSubTrigger>
                            <Plus aria-hidden="true" /> Add to playlist
                        </ContextMenuSubTrigger>
                        <ContextMenuSubContent className="w-52">
                            {ownedPlaylists === null ? (
                                <ContextMenuItem disabled>Loading…</ContextMenuItem>
                            ) : ownedPlaylists.map((p) => (
                                <ContextMenuItem key={p.id} onClick={() => addToPlaylist(p)}>
                                    <List aria-hidden="true" />
                                    <span className="min-w-0 flex-1 truncate">{p.title}</span>
                                </ContextMenuItem>
                            ))}
                            {ownedPlaylists?.length ? <ContextMenuSeparator /> : null}
                            <ContextMenuItem onClick={() => setCreatingPlaylist(true)}>
                                <Plus aria-hidden="true" /> New playlist…
                            </ContextMenuItem>
                        </ContextMenuSubContent>
                    </ContextMenuSub>
                ) : null}
                <ContextMenuSeparator />
                <ContextMenuItem
                    className={auth.user ? undefined : 'opacity-50'}
                    onClick={auth.user ? toggleFavourite : () => emitSignInPrompt('tracks')}
                >
                    <Star aria-hidden="true" fill={isFavourited ? 'currentColor' : 'none'} />
                    {isFavourited ? 'Unfavourite' : 'Favourite'}
                </ContextMenuItem>
                <ContextMenuSeparator />
                <ContextMenuItem render={<Link href={track.url} className="no-underline" />}>
                    <Music aria-hidden="true" /> Go to track
                </ContextMenuItem>
                {track.album ? (
                    <ContextMenuItem render={<Link href={track.album.url} className="no-underline" />}>
                        <LayoutGrid aria-hidden="true" /> Go to album
                    </ContextMenuItem>
                ) : null}
                <ContextMenuItem render={<Link href={track.user.url} className="no-underline" />}>
                    <User aria-hidden="true" /> Go to artist
                </ContextMenuItem>
            </ContextMenuContent>
            <PlaylistDialog
                open={creatingPlaylist}
                onClose={() => setCreatingPlaylist(false)}
                onSaved={(p) => addToPlaylist({ id: p.id, title: p.title })}
            />
        </ContextMenu>
    );
}
