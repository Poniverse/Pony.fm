import React from 'react';
import { Music, Pause, Play } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Img } from '../core/Img';
import { Loader } from '../core/Loader';

const S = { xs: 32, sm: 44, md: 64, lg: 120, xl: 260 } as const;

/** Square cover art with the hover play overlay from the tracks listing. */
export interface AlbumArtProps {
  src?: string;
  alt?: string;
  /** Named step or an explicit pixel size */
  size?: keyof typeof S | number;
  playing?: boolean;
  loading?: boolean;
  /** Renders the art as a spinning record in the player bar */
  vinyl?: boolean;
  /** Provide to render the play/pause overlay */
  onPlay?: React.MouseEventHandler;
}

export function AlbumArt({ src, alt = '', size = 'md', playing, onPlay, loading, vinyl }: AlbumArtProps) {
  const px = typeof size === 'number' ? size : (S[size] || S.md);
  return (
    <span
      className={cn('group relative block flex-none overflow-hidden bg-surface-3 shadow-(--ring-inset)', vinyl ? 'rounded-full' : 'rounded-art')}
      style={{ width: px, height: px }}>
      {/* Icon underlay shows until the art has loaded (or if it fails). */}
      <span className="absolute inset-0 grid place-items-center text-faint" aria-hidden="true"><Music style={{ width: Math.max(12, px * 0.3), height: Math.max(12, px * 0.3) }} /></span>
      {src ? <Img className={cn('pfm-anim relative block size-full object-cover', vinyl && playing && 'animate-[pfm-spin_7s_linear_infinite]')} src={src} alt={alt} /> : null}
      {vinyl ? <span aria-hidden="true" className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full bg-surface-1 shadow-[0_0_0_1px_rgba(0,0,0,0.35)]" style={{ width: Math.max(5, px * 0.14), height: Math.max(5, px * 0.14) }} /> : null}
      {onPlay ? (
        <button type="button" aria-label={playing ? 'Pause' : 'Play'} onClick={onPlay}
          className={cn(
            'absolute inset-0 grid cursor-pointer place-items-center border-none bg-black/0 text-white opacity-0 transition-[opacity,background] duration-(--dur-normal) ease-(--ease-standard) group-hover:bg-black/62 group-hover:opacity-100',
            playing && 'bg-black/62 opacity-100',
          )}>
          {loading
            ? <Loader size={Math.max(16, Math.round(px * 0.35))} />
            : playing
              ? <Pause aria-hidden="true" style={{ width: Math.max(11, px * 0.28), height: Math.max(11, px * 0.28) }} />
              : <Play aria-hidden="true" style={{ width: Math.max(11, px * 0.28), height: Math.max(11, px * 0.28) }} />}
        </button>
      ) : null}
    </span>
  );
}
