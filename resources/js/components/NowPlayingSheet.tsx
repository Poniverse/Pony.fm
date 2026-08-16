import React from 'react';
import { Link } from '@inertiajs/react';
import { ChevronDown, List, Pause, Play, Repeat, Repeat1, SkipBack, SkipForward } from 'lucide-react';
import { IconButton } from '@/design-system/core/IconButton';
import { Img } from '@/design-system/core/Img';
import { PonyfmMark } from '@/design-system/core/PonyfmMark';
import { QueuePanel } from '@/design-system/music/QueuePanel';
import { Transport } from '@/design-system/music/Transport';
import { usePlayer, usePlayerTime } from '@/lib/player/PlayerContext';
import { toDesignTrack } from '@/layouts/AppLayout';
import { formatDuration } from '@/lib/format';

/**
 * The mobile pull-out player: tapping the mini bar's now-playing cluster
 * expands into a full-screen sheet with big album art and the full
 * transport. Mobile-only — the desktop bar has room for everything.
 */
export function NowPlayingSheet({ open, onClose }: {
    open: boolean;
    onClose: () => void;
}) {
    const player = usePlayer();
    const time = usePlayerTime();
    const current = player.current;
    // The queue lives inside the sheet — swapping the art view for the list
    // keeps the player open instead of bouncing to the desktop drawer.
    const [showQueue, setShowQueue] = React.useState(false);

    React.useEffect(() => {
        if (!open) return;
        const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose(); };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [open, onClose]);

    if (!open || !current) return null;

    return (
        <div className="fixed inset-0 z-[1060] flex flex-col bg-surface-1 px-6 pt-4 pb-8 animate-in fade-in-0 slide-in-from-bottom-8 md:hidden">
            <div className="flex flex-none items-center justify-between">
                {/* The queue view brings its own "Up next" header with count. */}
                <span className="font-text text-2xs font-semibold uppercase tracking-caps text-faint">Now playing</span>
                <IconButton icon={ChevronDown} label="Collapse" onClick={onClose} />
            </div>

            {showQueue ? (
                <QueuePanel
                    className="min-h-0 w-full flex-1 border-l-0"
                    title="Up next"
                    items={player.queue.map(toDesignTrack)}
                    itemIds={player.queue.map((q) => q.queueId)}
                    currentIndex={player.queue.length ? player.index : undefined}
                    onPlay={(_t, i) => player.playAt(i)}
                    onReorder={player.moveInQueue}
                    onRemove={(_t, i) => player.removeFromQueue(i)}
                />
            ) : (
                <div className="flex min-h-0 flex-1 flex-col items-center justify-center gap-6">
                    <span className="relative block aspect-square w-full max-w-[min(340px,70vw)] flex-none overflow-hidden rounded-art bg-surface-3 shadow-(--ring-inset)">
                        <span className="absolute inset-0 grid place-items-center text-faint" aria-hidden="true">
                            <PonyfmMark className="size-16" />
                        </span>
                        <Img src={current.covers.normal} alt={current.title} className="relative size-full object-cover" />
                    </span>
                    <div className="grid w-full max-w-[340px] justify-items-center gap-1 text-center">
                        <Link href={current.url} onClick={onClose}
                            className="max-w-full truncate font-display text-xl font-semibold text-heading no-underline hover:underline">
                            {current.title}
                        </Link>
                        <Link href={current.user.url} onClick={onClose}
                            className="max-w-full truncate text-sm text-muted-foreground no-underline hover:underline">
                            {current.user.name}
                        </Link>
                    </div>
                </div>
            )}

            <div className="grid w-full flex-none gap-4">
                <div className="grid gap-1.5">
                    <Transport progress={time.progress} buffered={time.buffered} onSeek={player.seekTo} />
                    <div className="flex justify-between font-mono text-xs text-muted-foreground">
                        <span>{formatDuration(time.elapsed)}</span>
                        <span>{formatDuration(time.duration || Number(current.duration) || 0)}</span>
                    </div>
                </div>
                <div className="flex items-center justify-center gap-3">
                    <IconButton icon={player.repeatMode === 'one' ? Repeat1 : Repeat}
                        label={player.repeatMode === 'one' ? 'Repeat one' : 'Repeat'}
                        active={player.repeatMode !== 'off'} onClick={player.toggleRepeat} />
                    <IconButton icon={SkipBack} label="Previous" size="lg" onClick={player.playPrev} />
                    <IconButton icon={player.isPlaying ? Pause : Play} label={player.isPlaying ? 'Pause' : 'Play'}
                        variant="filled" size="lg" round className="size-14 text-xl" onClick={player.playPause} />
                    <IconButton icon={SkipForward} label="Next" size="lg" onClick={player.playNext} />
                    <IconButton icon={List} label="Queue" active={showQueue} onClick={() => setShowQueue((v) => !v)} />
                </div>
            </div>
        </div>
    );
}
