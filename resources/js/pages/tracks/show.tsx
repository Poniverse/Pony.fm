import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ChartColumn, Eye, LayoutGrid, Mic, MicOff, Pause, Pencil, Play, Search, Share2, TriangleAlert } from 'lucide-react';
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
import { ActionBar } from '@/components/ActionBar';
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
                    <header className="flex flex-col gap-5 md:flex-row md:items-end">
                        <AlbumArt src={track.covers.normal} alt={track.title} size={132} playing={playing} onPlay={() => player.playTracks([track])} />
                        <div className="grid min-w-0 flex-1 gap-2">
                            <div className="flex flex-wrap items-center gap-1.5">
                                <Badge tone={track.is_vocal ? 'vocal' : 'neutral'} icon={track.is_vocal ? Mic : MicOff}>
                                    {track.is_vocal ? 'Vocal' : 'Instrumental'}
                                </Badge>
                                {!track.is_published ? <Badge tone="warning" icon={Eye}>Unpublished — only you can see this</Badge> : null}
                                {track.is_explicit ? <Badge tone="danger" icon={TriangleAlert}>Explicit</Badge> : null}
                                {track.album ? <Badge tone="brand" icon={LayoutGrid}>From {track.album.title}</Badge> : null}
                            </div>
                            <h1 className="text-2xl font-semibold leading-tight md:text-3xl">{track.title}</h1>
                            <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                                <Avatar name={track.user.name} size="xs" />
                                <Link href={track.user.url}>{track.user.name}</Link>
                                <span className="text-faint">
                                    {track.genre ? '· ' + track.genre.name + ' ' : ''}· {formatDuration(track.duration)}
                                </span>
                            </div>
                            <ActionBar className="mt-1">
                                <Button icon={playing ? Pause : Play} onClick={() => player.playTracks([track])}>{playing ? 'Pause' : 'Play'}</Button>
                                {track.formats?.length ? <DownloadMenu formats={track.formats as DownloadFormat[]} resourceType="tracks" resourceId={track.id} /> : null}
                                <FavouriteButton favourited={favourited} onToggle={toggleFavourite} what="tracks" />
                                <AddToPlaylist trackId={track.id} />
                                <Button variant="ghost" icon={Share2} onClick={() => { if (!shareNatively(track.share, track.title + ' \u00b7 ' + track.user.name)) setShare(true); }}>Share</Button>
                                {track.permissions?.edit ? (
                                    <>
                                        <Button render={<Link href={track.url + '/edit'} />} variant="ghost" icon={Pencil}>Edit</Button>
                                        <Button render={<Link href={track.url + '/stats'} />} variant="ghost" icon={ChartColumn}>Stats</Button>
                                    </>
                                ) : null}
                            </ActionBar>
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
