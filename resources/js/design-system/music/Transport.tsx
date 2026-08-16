import React from 'react';
import { cn } from '@/lib/utils';
import { Slider } from '@/design-system/primitives/slider';

/** Seek bar: buffered band behind, played band in purple, knob on hover. */
export interface TransportProps {
  /** 0–1 */
  progress?: number;
  /** 0–1 loaded fraction */
  buffered?: number;
  height?: number;
  onSeek?: (fraction: number) => void;
}

export function Transport({ progress = 0, buffered = 0, onSeek, height = 6 }: TransportProps) {
  return (
    <Slider
      aria-label="Seek"
      value={[Math.min(1, Math.max(0, progress))]}
      min={0}
      max={1}
      step={0.001}
      disabled={!onSeek}
      onValueChange={(v) => onSeek?.((Array.isArray(v) ? v[0] : v) ?? 0)}
      className={cn('group data-[disabled]:opacity-100', onSeek ? 'cursor-pointer' : 'cursor-default')}
      style={{ height }}
      trackClassName="rounded-pill bg-(--player-transport-track) data-[orientation=horizontal]:h-full"
      rangeClassName="rounded-pill bg-(--player-transport-played)"
      thumbClassName="size-[11px] scale-0 rounded-full border-0 bg-white shadow-sm transition-transform duration-(--dur-fast) ease-(--ease-out) group-hover:scale-100 hover:ring-0 focus-visible:ring-0"
    >
      <div
        className="absolute inset-y-0 left-0 rounded-pill bg-(--player-transport-buffer)"
        style={{ width: (buffered * 100) + '%' }}
      />
    </Slider>
  );
}
