import React from 'react';
import { DragDropProvider } from '@dnd-kit/react';
import { useSortable } from '@dnd-kit/react/sortable';
import { move } from '@dnd-kit/helpers';
import { Play, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { AlbumArt } from './AlbumArt';
import { IconButton } from '../core/IconButton';
import type { Track } from './TrackRow';

/**
 * Slide-in queue column. Opened from the player's queue button; sortable via
 * dnd-kit, so rows shift and animate around the dragged item.
 */
export interface QueuePanelProps {
  items: Track[];
  /** Stable per-entry ids (required for reordering — the same track can be queued twice). */
  itemIds?: string[];
  /** Highlight by queue position. */
  currentIndex?: number;
  /** Fallback highlight by id, for callers without positional state. */
  currentId?: string | number;
  title?: string;
  footer?: React.ReactNode;
  onPlay?: (t: Track, index: number) => void;
  onRemove?: (t: Track, index: number) => void;
  /** Enables drag-and-drop reordering. */
  onReorder?: (from: number, to: number) => void;
  /** Wrap a row (e.g. in a context menu). Must not alter layout. */
  wrapRow?: (row: React.ReactElement, index: number) => React.ReactNode;
  onClose?: () => void;
}

function QueueRow({ id, index, on, sortable, onClick, children }: {
  id: string;
  index: number;
  on: boolean;
  sortable: boolean;
  onClick?: () => void;
  children: React.ReactNode;
}) {
  const { ref, isDragging } = useSortable({ id, index, disabled: !sortable });
  return (
    <div ref={ref} onClick={onClick}
      className={cn(
        'flex cursor-pointer items-center gap-2.5 rounded-sm px-2 py-1.5 transition-[background] duration-(--dur-fast) ease-(--ease-standard)',
        on ? 'bg-surface-active' : 'bg-transparent hover:bg-surface-hover',
        sortable && 'cursor-grab active:cursor-grabbing',
        isDragging && 'z-10 opacity-90 shadow-pop',
      )}>
      {children}
    </div>
  );
}

export function QueuePanel({ items = [], itemIds, currentIndex, currentId, title = 'Up next', onPlay, onRemove, onReorder, wrapRow, onClose, footer }: QueuePanelProps) {
  const ids = React.useMemo(
    () => itemIds ?? items.map((t, i) => String(t.id ?? 'q') + '-' + i),
    [itemIds, items],
  );

  const handleDragEnd = (event: Parameters<typeof move>[1]) => {
    if (!onReorder) return;
    const after = move(ids.map((id) => ({ id })), event).map((x) => x.id);
    let first = 0;
    while (first < ids.length && ids[first] === after[first]) first++;
    let last = ids.length - 1;
    while (last >= 0 && ids[last] === after[last]) last--;
    if (first >= last) return; // nothing moved
    if (ids[first] === after[last]) onReorder(first, last); // moved down
    else onReorder(last, first); // moved up
  };

  return (
    <aside className="flex w-[340px] flex-none flex-col border-l border-border bg-surface-1">
      <div className="flex items-center gap-2 border-b border-border-subtle px-4 py-3.5">
        <span className="flex-1 text-2xs font-bold uppercase tracking-caps text-faint">{title}</span>
        <span className="font-mono text-2xs text-faint">{items.length}</span>
        {onClose ? <IconButton icon={X} label="Close queue" size="sm" onClick={onClose} /> : null}
      </div>
      <div className="grid min-h-0 flex-1 content-start gap-0.5 overflow-y-auto p-2">
        <DragDropProvider onDragEnd={handleDragEnd}>
          {items.map((t, i) => {
            const on = currentIndex != null ? i === currentIndex : t.id === currentId;
            const row = (
              <QueueRow id={ids[i]} index={i} on={on} sortable={!!onReorder}
                onClick={() => onPlay && onPlay(t, i)}>
                <span className={cn('w-[18px] text-center font-mono text-2xs', on ? 'text-brand-text' : 'text-faint')}>
                  {on ? <Play aria-hidden="true" className="inline size-2.5" /> : i + 1}
                </span>
                <AlbumArt src={t.cover} alt={t.title} size="xs" />
                <span className="grid min-w-0 flex-1">
                  <span className={cn('truncate text-sm', on ? 'font-semibold text-brand-text' : 'font-normal text-heading')}>{t.title}</span>
                  <span className="truncate text-xs text-muted-foreground">{t.artist}</span>
                </span>
                <span className="font-mono text-xs text-faint">{t.duration}</span>
                {onRemove ? <IconButton icon={X} label={'Remove ' + t.title} size="sm" onClick={(e) => { e.stopPropagation(); onRemove(t, i); }} /> : null}
              </QueueRow>
            );
            return (
              <React.Fragment key={ids[i]}>
                {wrapRow ? wrapRow(row, i) : row}
              </React.Fragment>
            );
          })}
        </DragDropProvider>
      </div>
      {footer ? <div className="border-t border-border-subtle px-4 py-2.5 text-2xs text-faint">{footer}</div> : null}
    </aside>
  );
}
