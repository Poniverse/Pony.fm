import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ChartColumn, Eye, Mic, MicOff, Pause, Pencil, Play, Search, Share2, TriangleAlert } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { AlbumArt } from '@/design-system/music/AlbumArt';
import { StatList } from '@/design-system/music/StatList';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';
import { Dialog } from '@/design-system/feedback/Dialog';
import { Button } from '@/design-system/core/Button';
import { Badge } from '@/design-system/core/Badge';
import { Avatar } from '@/design-system/core/Avatar';
import { usePlayer } from '@/lib/player/PlayerContext';
import { Markdown } from '@/lib/markdown';
import { api } from '@/lib/api';
import { formatDate, formatDuration } from '@/lib/format';
import { IconButton } from '@/design-system/core/IconButton';
import { AddToPlaylist } from '@/components/AddToPlaylist';
import { CommentsSection } from '@/components/CommentsSection';
import { FavouriteButton } from '@/components/FavouriteButton';
import { DownloadMenu, type DownloadFormat } from '@/components/DownloadMenu';
import { ShareDialog, shareNatively } from '@/components/ShareDialog';
import type { TrackShow } from '@/lib/types';

export default function TrackShowPage({ track }: { track: TrackShow }) {
    const player = usePlayer();
    const playing = player.isCurrent(track.id) && player.isPlaying;
    const [favourited, setFavourited] = React.useState(!!track.user_data?.is_favourited);
    const [share, setShare] = React.useState(false);
    const [lyricsOpen, setLyricsOpen] = React.useState(false);
    const [lightbox, setLightbox] = React.useState(false);

    React.useEffect(() => setFavourited(!!track.user_data?.is_favourited), [track.id, track.user_data?.is_favourited]);

    // Log the view now that the page is actually displayed — the Inertia
    // request itself doesn't count views (hover prefetches would skew them).
    React.useEffect(() => {
        void api.post('/views', { type: 'track', id: track.id }).catch(() => undefined);
    }, [track.id]);

    const toggleFavourite = () => {
        setFavourited((f) => !f);
        api.post<{ is_favourited: boolean }>('/favourites/toggle', { type: 'track', id: track.id })
            .then(({ data }) => setFavourited(data.is_favourited))
            .catch(() => setFavourited((f) => !f));
    };

    const stats = [
        ...(track.genre ? [{ label: 'Genre', value: <Link href={'/tracks?filter=genres-' + track.genre.id}>{track.genre.name}</Link>, mono: false }] : []),
        { label: 'Published', value: formatDate(track.published_at), mono: false },
        { label: 'Views', value: track.stats.views.toLocaleString() },
        { label: 'Plays', value: track.stats.plays.toLocaleString() },
        { label: 'Favourites', value: track.stats.favourites.toLocaleString() },
        { label: 'Comments', value: track.stats.comments.toLocaleString() },
        ...(track.is_downloadable ? [{ label: 'Downloads', value: track.stats.downloads.toLocaleString() }] : []),
    ];

    return (
        <div className="relative max-w-(--content-max) px-7 pt-6 pb-12">
            <Head title={track.title + ' - ' + track.user.name} />
            <div className="detail-columns">
                <div className="grid gap-7">
                    <header className="flex flex-col gap-4 md:flex-row md:items-start">
                        <AlbumArt src={track.covers.normal} alt={track.title} size={132} playing={playing} onPlay={() => player.playTracks([track])} />
                        <div className="grid min-w-0 flex-1 gap-1.5">
                            {!track.is_published || track.is_explicit ? (
                                <div className="flex flex-wrap items-center gap-1.5">
                                    {!track.is_published ? <Badge tone="warning" icon={Eye}>Unpublished — only you can see this</Badge> : null}
                                    {track.is_explicit ? <Badge tone="danger" icon={TriangleAlert}>Explicit</Badge> : null}
                                </div>
                            ) : null}
                            <div className="flex items-start justify-between gap-3">
                                <h1 className="min-w-0 flex-1 text-2xl font-semibold leading-tight md:text-3xl">{track.title}</h1>
                                <div className="flex flex-none items-center gap-0.5">
                                    {track.permissions?.edit ? (
                                        <>
                                            <IconButton icon={Pencil} label="Edit" render={<Link href={track.url + '/edit'} />} />
                                            <IconButton icon={ChartColumn} label="Stats" render={<Link href={track.url + '/stats'} />} />
                                        </>
                                    ) : null}
                                </div>
                            </div>
                            <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-muted-foreground">
                                <Avatar src={track.user.avatars?.small} name={track.user.name} size="xs" />
                                <Link href={track.user.url}>{track.user.name}</Link>
                                <span className="text-faint">
                                    {track.genre ? '· ' + track.genre.name + ' ' : ''}· {formatDuration(track.duration)} ·
                                </span>
                                <span title={track.is_vocal ? 'Vocal' : 'Instrumental'} className="inline-flex items-center text-faint">
                                    {track.is_vocal ? <Mic aria-label="Vocal" className="size-3.5" /> : <MicOff aria-label="Instrumental" className="size-3.5" />}
                                </span>
                                {track.album ? (
                                    <span className="text-faint">
                                        · from <Link href={track.album.url} className="text-muted-foreground">{track.album.title}</Link>
                                    </span>
                                ) : null}
                            </div>
                            <div className="mt-1.5 flex items-center gap-1.5">
                                <IconButton icon={playing ? Pause : Play} label={playing ? 'Pause' : 'Play'} variant="filled" round size="lg"
                                    onClick={() => player.playTracks([track])} />
                                <FavouriteButton iconOnly iconRound iconSize="lg" favourited={favourited} onToggle={toggleFavourite} what="tracks" />
                                {track.formats?.length ? (
                                    <DownloadMenu iconOnly iconRound iconSize="lg" formats={track.formats as DownloadFormat[]} resourceType="tracks" resourceId={track.id} />
                                ) : null}
                                <AddToPlaylist iconOnly iconRound iconSize="lg" trackId={track.id} />
                                <IconButton icon={Share2} label="Share" round size="lg"
                                    onClick={() => { if (!shareNatively(track.share, track.title + ' · ' + track.user.name)) setShare(true); }} />
                            </div>
                        </div>
                    </header>

                    {track.description ? (
                        <section>
                            <SectionHeader title="Description" level={2} />
                            <div className="max-w-[640px] text-md text-foreground">
                                <Markdown source={track.description} />
                            </div>
                        </section>
                    ) : null}

                    {track.lyrics ? (
                        <section>
                            <SectionHeader title="Lyrics" level={2}
                                action={<a href="#lyrics" onClick={(e) => { e.preventDefault(); setLyricsOpen((v) => !v); }}>{lyricsOpen ? 'Hide' : 'Reveal'}</a>} />
                            {lyricsOpen ? (
                                <div className="max-w-[640px] whitespace-pre-wrap text-md text-foreground">{track.lyrics}</div>
                            ) : null}
                        </section>
                    ) : null}

                    <CommentsSection type="track" id={track.id} initial={track.comments} />
                </div>

                <aside className="sticky top-0 grid gap-3">
                    <StatList items={stats} />
                    <a href="#cover" onClick={(e) => { e.preventDefault(); setLightbox(true); }}
                        className="text-xs text-link">
                        <Search className="mr-1 inline size-3.5" aria-hidden="true" /> View full-size cover art
                    </a>
                </aside>
            </div>

            <ShareDialog open={share} onClose={() => setShare(false)} title="this track" subtitle={track.title + ' · ' + track.user.name} share={track.share} />

            <Dialog open={lightbox} title={track.title} onClose={() => setLightbox(false)} width={720}>
                <img src={track.covers.original} alt={track.title + ' cover art, full size'} className="block w-full" />
            </Dialog>
        </div>
    );
}

TrackShowPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
