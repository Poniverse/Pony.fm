import React from 'react';
import { TrackRow } from '@/design-system/music/TrackRow';
import { TrackContextMenu } from '@/components/TrackContextMenu';
import { usePage } from '@inertiajs/react';
import { usePlayer } from '@/lib/player/PlayerContext';
import { toDesignTrack } from '@/layouts/AppLayout';
import { api } from '@/lib/api';
import { emitSignInPrompt } from '@/lib/events';
import type { SharedProps, TrackSummary } from '@/lib/types';

/**
 * A list of playable track rows. Play replaces the queue with this list
 * (matching the old playTracks semantics); right-click offers queueing,
 * favouriting and navigation; the star toggles a favourite through the
 * existing /api/web endpoint.
 */
export function TrackList({ tracks }: { tracks: TrackSummary[] }) {
    const player = usePlayer();
    const { auth } = usePage<SharedProps>().props;
    const [favourites, setFavourites] = React.useState<Record<number, boolean>>(() =>
        Object.fromEntries(tracks.map((t) => [t.id, !!t.user_data?.is_favourited])),
    );

    React.useEffect(() => {
        setFavourites(Object.fromEntries(tracks.map((t) => [t.id, !!t.user_data?.is_favourited])));
    }, [tracks]);

    const toggleFavourite = (id: number) => {
        if (!auth.user) {
            emitSignInPrompt('tracks');
            return;
        }
        setFavourites((f) => ({ ...f, [id]: !f[id] }));
        api.post<{ is_favourited: boolean }>('/favourites/toggle', { type: 'track', id })
            .then(({ data }) => setFavourites((f) => ({ ...f, [id]: data.is_favourited })))
            .catch(() => setFavourites((f) => ({ ...f, [id]: !f[id] })));
    };

    return (
        <div className="grid gap-0.5">
            {tracks.map((t, i) => (
                <TrackContextMenu
                    key={t.id}
                    track={t}
                    playContext={{ tracks, index: i }}
                    favourited={!!favourites[t.id]}
                    onFavourite={() => toggleFavourite(t.id)}
                >
                    <TrackRow
                        track={toDesignTrack(t)}
                        playing={player.isCurrent(t.id) && player.isPlaying}
                        favourited={!!favourites[t.id]}
                        onPlay={() => player.playTracks(tracks, i)}
                        onFavourite={() => toggleFavourite(t.id)}
                        href={t.url}
                    />
                </TrackContextMenu>
            ))}
        </div>
    );
}
