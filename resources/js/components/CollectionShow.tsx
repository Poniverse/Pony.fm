import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Pause, Play, Share2 } from 'lucide-react';
import { AlbumArt } from '@/design-system/music/AlbumArt';
import { StatList } from '@/design-system/music/StatList';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';
import { IconButton } from '@/design-system/core/IconButton';
import { Avatar } from '@/design-system/core/Avatar';
import { usePlayer } from '@/lib/player/PlayerContext';
import { Markdown } from '@/lib/markdown';
import { api } from '@/lib/api';
import { formatDuration } from '@/lib/format';
import { TrackList } from '@/components/TrackList';
import { CommentsSection } from '@/components/CommentsSection';
import { DownloadMenu, type DownloadFormat } from '@/components/DownloadMenu';
import { ShareDialog, shareNatively } from '@/components/ShareDialog';
import { FavouriteButton } from '@/components/FavouriteButton';
import type { AlbumShow, PlaylistShow } from '@/lib/types';

/** Album and playlist pages share a skeleton: cover header, play-all,
 *  downloads (with mixed-losslessness warning), track list, comments. */
export function CollectionShow({ kind, data, extraBadges, extraActions }: {
    kind: 'album' | 'playlist';
    data: AlbumShow | PlaylistShow;
    extraBadges?: React.ReactNode;
    extraActions?: React.ReactNode;
}) {
    const player = usePlayer();
    const [favourited, setFavourited] = React.useState(!!data.user_data?.is_favourited);
    const [share, setShare] = React.useState(false);

    React.useEffect(() => setFavourited(!!data.user_data?.is_favourited), [data.id, data.user_data?.is_favourited]);

    // Log the view now that the page is actually displayed — the Inertia
    // request itself doesn't count views (hover prefetches would skew them).
    React.useEffect(() => {
        void api.post('/views', { type: kind, id: data.id }).catch(() => undefined);
    }, [kind, data.id]);

    const tracks = data.tracks;
    const isCurrentCollection = tracks.some((t) => player.isCurrent(t.id));
    const playing = isCurrentCollection && player.isPlaying;
    const totalSeconds = tracks.reduce((acc, t) => acc + (Number(t.duration) || 0), 0);

    const toggleFavourite = () => {
        setFavourited((f) => !f);
        api.post<{ is_favourited: boolean }>('/favourites/toggle', { type: kind, id: data.id })
            .then(({ data: d }) => setFavourited(d.is_favourited))
            .catch(() => setFavourited((f) => !f));
    };

    const stats = [
        { label: 'Tracks', value: tracks.length },
        { label: 'Length', value: formatDuration(totalSeconds) },
        ...(data.stats ? [
            { label: 'Views', value: data.stats.views.toLocaleString() },
            { label: 'Favourites', value: data.stats.favourites.toLocaleString() },
            { label: 'Comments', value: data.stats.comments.toLocaleString() },
            { label: 'Downloads', value: data.stats.downloads.toLocaleString() },
        ] : []),
    ];

    return (
        <div className="relative max-w-(--content-max) px-7 pt-6 pb-12">
            <Head title={data.title + ' - ' + data.user.name} />
            <div className="detail-columns">
                <div className="grid gap-7">
                    <header className="flex flex-col gap-4 md:flex-row md:items-start">
                        <AlbumArt src={data.covers.normal} alt={data.title} size={132} playing={playing}
                            onPlay={() => player.playTracks(tracks)} />
                        <div className="grid min-w-0 flex-1 gap-1.5">
                            {extraBadges ? (
                                <div className="flex flex-wrap items-center gap-1.5">{extraBadges}</div>
                            ) : null}
                            <div className="flex items-start justify-between gap-3">
                                <h1 className="min-w-0 flex-1 text-2xl font-semibold leading-tight md:text-3xl">{data.title}</h1>
                                {extraActions ? (
                                    <div className="flex flex-none items-center gap-0.5">{extraActions}</div>
                                ) : null}
                            </div>
                            <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-muted-foreground">
                                <Avatar src={data.user.avatars?.small} name={data.user.name} size="xs" />
                                <Link href={data.user.url}>{data.user.name}</Link>
                                <span className="text-faint">· {kind} · {tracks.length} tracks · {formatDuration(totalSeconds)}</span>
                            </div>
                            <div className="mt-1.5 flex items-center gap-1.5">
                                <IconButton icon={playing ? Pause : Play} label={playing ? 'Pause' : 'Play all'} variant="filled" round size="lg"
                                    onClick={() => player.playTracks(tracks)} />
                                <FavouriteButton iconOnly iconRound iconSize="lg" favourited={favourited} onToggle={toggleFavourite} what={kind + 's'} />
                                {data.formats?.length ? (
                                    <DownloadMenu
                                        iconOnly iconRound iconSize="lg"
                                        formats={data.formats as DownloadFormat[]}
                                        resourceType={kind === 'album' ? 'albums' : 'playlists'}
                                        resourceId={data.id}
                                        shouldConfirm={(f) => f.isMixedLosslessness
                                            ? 'Not every track in this ' + kind + ' has a lossless master, so some files in this download will be lossy.'
                                            : null}
                                    />
                                ) : null}
                                <IconButton icon={Share2} label="Share" round size="lg"
                                    onClick={() => { if (!shareNatively(data.share, data.title + ' \u00b7 ' + data.user.name)) setShare(true); }} />
                            </div>
                        </div>
                    </header>

                    <section>
                        <SectionHeader title="Tracks" count={tracks.length} />
                        <TrackList tracks={tracks} />
                    </section>

                    {data.description ? (
                        <section>
                            <SectionHeader title="Description" level={2} />
                            <div className="max-w-[640px] text-md text-foreground">
                                <Markdown source={data.description} />
                            </div>
                        </section>
                    ) : null}

                    <CommentsSection type={kind} id={data.id} initial={data.comments} />
                </div>

                <aside className="sticky top-0 grid gap-6">
                    <StatList items={stats} />
                </aside>
            </div>

            <ShareDialog open={share} onClose={() => setShare(false)} title={'this ' + kind} subtitle={data.title + ' · ' + data.user.name} share={data.share} />
        </div>
    );
}
