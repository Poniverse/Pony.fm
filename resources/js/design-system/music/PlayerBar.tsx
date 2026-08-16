import React from 'react';
import { Link } from '@inertiajs/react';
import { List, Pause, Play, Repeat, Repeat1, Shuffle, SkipBack, SkipForward, Volume1, Volume2, VolumeX } from 'lucide-react';
import { Popover, PopoverContent, PopoverTrigger } from '@/design-system/primitives/popover';
import { cn } from '@/lib/utils';
import { Transport } from './Transport';
import { IconButton } from '../core/IconButton';
import { PonyfmMark } from '../core/PonyfmMark';
import type { Track } from './TrackRow';

/**
 * The persistent now-playing bar. Spans the content column beside the sidebar —
 * pinned to the bottom by default in the app shell.
 */
export interface PlayerBarProps {
  track?: Track;
  playing?: boolean;
  /** 0–1 */
  progress?: number;
  buffered?: number;
  elapsed?: string;
  duration?: string;
  repeat?: boolean;
  /** Renders a small "1" badge on the repeat button (repeat-one mode) */
  repeatOne?: boolean;
  shuffle?: boolean;
  /** Which edge it is pinned to — flips the divider border */
  position?: 'top' | 'bottom';
  /** Pass with onToggleQueue to render the queue button in a pressed state */
  queueOpen?: boolean;
  onPlayPause?: () => void;
  onNext?: () => void;
  onPrev?: () => void;
  onSeek?: (fraction: number) => void;
  onToggleRepeat?: () => void;
  onToggleShuffle?: () => void;
  /** Provide to render the queue button */
  onToggleQueue?: () => void;
  /** 0–100; provide with onVolume to enable the volume popover */
  volume?: number;
  onVolume?: (volume: number) => void;
  /** Wrap the art + title cluster (e.g. in a context menu). Must not alter layout. */
  wrapNowPlaying?: (node: React.ReactElement) => React.ReactNode;
  /** The track page's URL — renders the art and title as real links. Prefer this over onOpen. */
  href?: string;
  /** Clicking the art or title opens the track's page. */
  onOpen?: () => void;
}

export function PlayerBar({ track, playing, progress = 0, buffered = 0, elapsed = '0:00', duration = '0:00', repeat, repeatOne, shuffle, position = 'top', queueOpen, onPlayPause, onNext, onPrev, onSeek, onToggleRepeat, onToggleShuffle, onToggleQueue, volume, onVolume, wrapNowPlaying, href, onOpen }: PlayerBarProps) {
  const t = track || ({} as Track);
  const [volumeOpen, setVolumeOpen] = React.useState(false);
  return (
    <div className={cn(
      'flex h-(--nowplaying-height) items-center gap-4 bg-surface-1 px-5 py-0',
      position === 'top' && 'border-b border-border',
      position === 'bottom' && 'border-t border-border',
    )}>
      <div className="flex flex-none items-center gap-0.5">
        <IconButton icon={SkipBack} label="Previous" onClick={onPrev} />
        <IconButton icon={playing ? Pause : Play} label={playing ? 'Pause' : 'Play'} variant="filled" size="lg" round onClick={onPlayPause} />
        <IconButton icon={SkipForward} label="Next" onClick={onNext} />
      </div>
      {(() => {
        const artClass = cn(
          'relative flex size-11 flex-none items-center justify-center overflow-hidden rounded-full bg-surface-3 text-faint shadow-(--ring-inset)',
          track && onOpen && 'cursor-pointer',
        );
        const artContent = track ? (
          <img
            src={t.cover}
            alt={t.title}
            className={cn('pfm-anim size-full rounded-full object-cover', playing && 'animate-[pfm-spin_7s_linear_infinite]')}
          />
        ) : (
          <PonyfmMark className="size-7" />
        );
        const titleClass = cn('truncate text-sm font-bold leading-snug text-heading', track && onOpen && 'cursor-pointer hover:underline');
        const cluster = (
          <div className="flex min-w-0 flex-1 items-center gap-4">
            {track && href ? (
              <Link href={href} className={artClass}>{artContent}</Link>
            ) : (
              <span onClick={track && onOpen ? onOpen : undefined} className={artClass}>{artContent}</span>
            )}
            <div className="grid min-w-0 flex-1 gap-[5px]">
              <div className="grid min-w-0">
                {track && href ? (
                  <Link href={href} className={cn(titleClass, 'max-w-full justify-self-start no-underline hover:underline')}>
                    {t.title || 'Nothing playing'}
                  </Link>
                ) : (
                  <span onClick={track && onOpen ? onOpen : undefined} className={titleClass}>
                    {t.title || 'Nothing playing'}
                  </span>
                )}
                {t.artist ? <span className="truncate text-xs leading-snug text-muted-foreground">{t.artist}</span> : null}
              </div>
              <Transport progress={progress} buffered={buffered} onSeek={onSeek} />
            </div>
          </div>
        );
        return wrapNowPlaying && track ? wrapNowPlaying(cluster) : cluster;
      })()}
      <span className="flex-none font-mono text-xs text-muted-foreground">{elapsed} / {duration}</span>
      <div className="flex flex-none items-center gap-0.5">
        <IconButton icon={Shuffle} label="Shuffle" active={shuffle} onClick={onToggleShuffle} />
        {onToggleQueue ? <IconButton icon={List} label="Queue" active={queueOpen} onClick={onToggleQueue} /> : null}
        <IconButton icon={repeatOne ? Repeat1 : Repeat} label={repeatOne ? 'Repeat one' : 'Repeat'} active={repeat} onClick={onToggleRepeat} />
        {onVolume != null ? (
          <Popover open={volumeOpen} onOpenChange={setVolumeOpen}>
            {/* Glyph tracks the level: muted, quiet (≤ half) or loud. */}
            <PopoverTrigger
              render={<IconButton icon={volume === 0 ? VolumeX : (volume ?? 100) <= 50 ? Volume1 : Volume2} label="Volume" active={volumeOpen} />}
            />
            <PopoverContent side={position === 'bottom' ? 'top' : 'bottom'} align="center" sideOffset={8}
              className="grid w-auto place-items-center bg-surface-raised px-2.5 py-3.5">
              <input type="range" min={0} max={100} value={volume ?? 100} aria-label="Volume"
                onChange={(e) => onVolume(Number(e.target.value))}
                className="h-[90px] accent-purple-400 [direction:rtl] [writing-mode:vertical-lr]" />
            </PopoverContent>
          </Popover>
        ) : (
          <IconButton icon={Volume2} label="Volume" />
        )}
      </div>
    </div>
  );
}
