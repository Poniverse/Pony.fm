import React from 'react';
import { Link } from '@inertiajs/react';
import { AlbumArt } from './AlbumArt';

/** Album / playlist / artist tile used by the grid listings. */
export interface CollectionCardProps {
  title: string;
  subtitle?: string;
  cover?: string;
  count?: number;
  countLabel?: string;
  /** Renders the card as a real link. */
  href?: string;
  /** For non-navigation cards (e.g. opening an editor). Prefer href. */
  onClick?: React.MouseEventHandler;
  onPlay?: () => void;
}

export function CollectionCard({ title, subtitle, cover, count, countLabel = 'tracks', href, onClick, onPlay }: CollectionCardProps) {
  const className = 'flex cursor-pointer items-center gap-3 rounded-card border border-border-subtle bg-card p-2 no-underline transition-[background,color,border-color] duration-(--dur-fast) ease-(--ease-standard) hover:bg-surface-3';
  const content = (
    <>
      <AlbumArt src={cover} alt={title} size="md" onPlay={onPlay ? (e) => { e.stopPropagation(); e.preventDefault(); onPlay(); } : undefined} />
      <span className="grid min-w-0 flex-1 gap-[3px]">
        <span className="truncate font-display text-lg font-semibold text-heading">{title}</span>
        <span className="truncate text-xs text-muted-foreground">{subtitle}</span>
      </span>
      {count != null ? (
        <span className="flex-none text-right">
          <span className="block font-mono text-md text-heading">{count}</span>
          <span className="block text-2xs uppercase tracking-caps text-faint">{countLabel}</span>
        </span>
      ) : null}
    </>
  );

  return href
    ? <Link href={href} className={className}>{content}</Link>
    : <div onClick={onClick} className={className}>{content}</div>;
}
