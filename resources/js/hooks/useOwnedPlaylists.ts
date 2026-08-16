import { useCallback, useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { api } from '@/lib/api';
import { onPlaylistsChanged } from '@/lib/events';
import type { SharedProps } from '@/lib/types';

export interface OwnedPlaylist {
    id: number;
    title: string;
    track_ids?: number[];
}

// Module-level cache so every context menu doesn't refetch; invalidated by
// the playlists-changed signal.
let cache: OwnedPlaylist[] | null = null;

/** The signed-in user's own playlists, fetched lazily when enabled. */
export function useOwnedPlaylists(enabled: boolean) {
    const { auth } = usePage<SharedProps>().props;
    const userId = auth.user?.id;
    const [playlists, setPlaylists] = useState<OwnedPlaylist[] | null>(cache);

    const load = useCallback(() => {
        if (userId == null) return;
        api.get<OwnedPlaylist[]>('/users/' + userId + '/playlists')
            .then(({ data }) => {
                cache = data;
                setPlaylists(data);
            })
            .catch(() => undefined);
    }, [userId]);

    useEffect(() => {
        if (enabled && cache === null) load();
        else if (enabled) setPlaylists(cache);
    }, [enabled, load]);

    useEffect(() => onPlaylistsChanged(() => {
        cache = null;
        if (enabled) load();
    }), [enabled, load]);

    return playlists;
}
