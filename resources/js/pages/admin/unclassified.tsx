import React from 'react';
import { Head } from '@inertiajs/react';
import { CircleCheck, Tags } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { AdminHeader } from '@/components/AdminHeader';
import { TrackEditor } from '@/components/TrackEditor';
import { Pagination } from '@/design-system/navigation/Pagination';
import { EmptyState } from '@/design-system/feedback/EmptyState';
import { AlbumArt } from '@/design-system/music/AlbumArt';
import { usePlayer } from '@/lib/player/PlayerContext';
import { useAdminTracks } from '@/hooks/useAdminTracks';
import { DEFAULT_FILTERS } from '@/lib/filters';
import { cn } from '@/lib/utils';

/** The classifier: unclassified queue on the left, live editor on the right. */
export default function AdminClassifierPage() {
    const [page, setPage] = React.useState(1);
    const { tracks, totalPages, loading, refresh } = useAdminTracks('/admin/tracks/unclassified', DEFAULT_FILTERS, page);
    const [selectedId, setSelectedId] = React.useState<number | null>(null);
    const player = usePlayer();

    return (
        <div className="grid max-w-(--content-max) gap-4 px-7 pt-6 pb-12">
            <Head title="Admin - Classifier" />
            <AdminHeader active="unclassified" />
            <div className="grid grid-cols-[minmax(280px,380px)_minmax(0,1fr)] items-start gap-6">
                <div className="grid gap-2">
                    {loading ? (
                        <p className="m-0 text-sm text-muted-foreground">Loading queue…</p>
                    ) : tracks.length === 0 ? (
                        <EmptyState icon={CircleCheck} title="Queue is empty">Every track is classified. Nice.</EmptyState>
                    ) : (
                        <div className="grid gap-0.5">
                            {tracks.map((t) => (
                                <div key={t.id} onClick={() => setSelectedId(t.id)}
                                    className={cn(
                                        'flex cursor-pointer items-center gap-2.5 rounded-sm px-2 py-1.5 transition-[background] duration-(--dur-fast) ease-(--ease-standard)',
                                        selectedId === t.id ? 'bg-surface-active' : 'bg-transparent',
                                    )}>
                                    <AlbumArt src={t.covers.thumbnail} alt={t.title} size="xs"
                                        playing={player.isCurrent(t.id) && player.isPlaying}
                                        onPlay={(e) => { e.stopPropagation(); player.playTracks([t]); }} />
                                    <span className="grid min-w-0 flex-1">
                                        <span className="truncate text-sm text-heading">{t.title}</span>
                                        <span className="truncate text-2xs text-faint">{t.user.name}</span>
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                    {totalPages > 1 ? <Pagination page={page} pages={totalPages} onChange={setPage} /> : null}
                </div>
                <div>
                    {selectedId != null ? (
                        <TrackEditor key={selectedId} trackId={selectedId}
                            onSaved={() => { setSelectedId(null); refresh(); }}
                            onDeleted={() => { setSelectedId(null); refresh(); }} />
                    ) : (
                        <EmptyState icon={Tags} title="Pick a track to classify">
                            Choose something from the queue; the editor opens here.
                        </EmptyState>
                    )}
                </div>
            </div>
        </div>
    );
}

AdminClassifierPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
