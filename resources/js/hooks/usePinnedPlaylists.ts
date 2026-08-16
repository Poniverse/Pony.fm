import { useCallback, useEffect, useState } from 'react';
import { api } from '@/lib/api';
import { onPlaylistsChanged } from '@/lib/events';
import type { PlaylistSummary } from '@/lib/types';

/** The signed-in user's pinned playlists for the sidebar. Refreshes whenever
 *  any component emits the playlists-changed signal (create/edit/delete). */
export function usePinnedPlaylists(enabled: boolean) {
    const [playlists, setPlaylists] = useState<PlaylistSummary[]>([]);

    const refresh = useCallback(() => {
        if (!enabled) return;
        api.get<PlaylistSummary[]>('/playlists/pinned')
            .then(({ data }) => setPlaylists(data))
            .catch(() => undefined);
    }, [enabled]);

    useEffect(refresh, [refresh]);
    useEffect(() => onPlaylistsChanged(refresh), [refresh]);

    return { playlists, refresh };
}
