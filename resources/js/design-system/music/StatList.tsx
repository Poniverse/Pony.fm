import React from 'react';
import { cn } from '@/lib/utils';

/** The genre/published/views/plays/downloads sidebar list from the track page. */
export interface StatItem { label: string; value: React.ReactNode; mono?: boolean }
export interface StatListProps { items: StatItem[] }

export function StatList({ items = [] }: StatListProps) {
  return (
    <dl className="m-0 grid gap-0">
      {items.map((it, i) => (
        <div key={i} className="flex justify-between gap-3 border-b border-border-subtle px-0 py-[9px]">
          <dt className="text-xs uppercase tracking-wide text-faint">{it.label}</dt>
          <dd className={cn('m-0 text-right text-xs text-heading', it.mono === false ? 'font-text' : 'font-mono')}>{it.value}</dd>
        </div>
      ))}
    </dl>
  );
}
