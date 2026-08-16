import React from 'react';
import { Link } from '@inertiajs/react';
import { MessageSquare, Mic, MicOff, Play, Star } from 'lucide-react';
import { cn } from '@/lib/utils';
import { AlbumArt } from './AlbumArt';

/**
 * One row of the tracks listing — art, title, artist/genre, play & favourite counts.
 */
export interface Track {
  id?: string | number;
  title: string;
  artist: string;
  genre?: string;
  cover?: string;
  duration?: string;
  isVocal?: boolean;
  stats?: { plays?: number; favourites?: number; comments?: number };
}
export interface TrackRowProps {
  track: Track;
  playing?: boolean;
  favourited?: boolean;
  onPlay?: (t: Track) => void;
  onFavourite?: (t: Track) => void;
  /** Renders the title as a real link to the track's page. Prefer this over onOpen. */
  href?: string;
  onOpen?: React.MouseEventHandler;
}

export function TrackRow({ track, playing, favourited, onPlay, onFavourite, href, onOpen }: TrackRowProps) {
  const t = track || ({} as Track);
  const stats = t.stats || {};
  const titleClass = cn('truncate font-text text-sm font-semibold', playing ? 'text-brand-text' : 'text-heading');
  return (
    <div onClick={onOpen}
      className={cn(
        'group relative flex h-(--track-row-height) items-center gap-3 rounded-sm py-0 pr-2.5 pl-1.5 transition-[background] duration-(--dur-fast) ease-(--ease-standard)',
        (onOpen || href) && 'cursor-pointer',
        playing ? 'bg-surface-active' : 'bg-transparent hover:bg-surface-hover',
      )}>
      <span className="relative z-10 flex-none">
        <AlbumArt src={t.cover} alt={t.title} size="sm" playing={playing} onPlay={(e) => { e.stopPropagation(); onPlay && onPlay(t); }} />
      </span>
      <span className="grid min-w-0 flex-1 gap-0.5">
        {href ? (
          <Link href={href} onClick={(e) => e.stopPropagation()}
            className={cn(titleClass, 'max-w-full justify-self-start no-underline hover:underline', 'static after:absolute after:inset-0')}>
            {t.title}
          </Link>
        ) : (
          <span className={titleClass}>{t.title}</span>
        )}
        <span className="truncate text-xs text-muted-foreground">
          {t.artist}{t.genre ? <span className="text-faint">{' · ' + t.genre}</span> : null}
        </span>
      </span>
      {playing ? (
        <span aria-hidden="true" className="flex h-[13px] items-end gap-0.5">
          {[0, 1, 2, 3].map((i) => (
            <span key={i} className="pfm-anim h-[13px] w-0.5 origin-bottom rounded-[1px] bg-purple-400" style={{
              animation: 'pfm-eq ' + (620 + i * 130) + 'ms ease-in-out infinite',
              animationDelay: (i * 90) + 'ms',
            }} />
          ))}
        </span>
      ) : null}
      <span className="flex flex-col items-end gap-1">
        <span className="flex items-center gap-0.5">
          {t.duration ? <span className="mr-1.5 font-mono text-xs text-faint">{t.duration}</span> : null}
          <span aria-hidden="true" title={t.isVocal ? 'Vocal' : 'Instrumental'}
            className={cn('flex w-[18px] justify-center text-faint', !t.isVocal && 'opacity-50')}>
            {t.isVocal ? <Mic className="size-3.5" /> : <MicOff className="size-3.5" />}
          </span>
          {onFavourite ? (
            <button type="button" aria-label="Favourite" onClick={(e) => { e.stopPropagation(); onFavourite(t); }}
              className={cn(
                'relative z-10 cursor-pointer border-none bg-transparent p-1 text-sm',
                favourited ? 'text-favourite [text-shadow:0_0_4px_rgba(0,0,0,0.6)]' : 'text-faint group-hover:text-muted-foreground',
              )}>
              <Star aria-hidden="true" fill={favourited ? 'currentColor' : 'none'}
                className={cn('pfm-anim inline-block size-3.5', favourited && 'animate-[pfm-pop_380ms_var(--ease-out)]')} />
            </button>
          ) : null}
        </span>
        <span className="flex gap-2.5 font-mono text-xs text-faint">
          <span title={stats.plays + ' plays'}><Play className="mr-1 inline size-2.5" aria-hidden="true" />{stats.plays}</span>
          <span title={stats.favourites + ' favourites'}><Star className="mr-1 inline size-2.5" aria-hidden="true" />{stats.favourites}</span>
          <span title={stats.comments + ' comments'}><MessageSquare className="mr-1 inline size-2.5" aria-hidden="true" />{stats.comments}</span>
        </span>
      </span>
    </div>
  );
}
