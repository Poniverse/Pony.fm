import React from 'react';
import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * The permanent left rail. Sections mirror the live site: browse links,
 * account links, then pinned playlists.
 *
 * Items with an href render as real anchors (external URLs open in a new
 * tab); items with onClick are action buttons (e.g. the theme toggle).
 */
export interface SidebarItem {
  label: string;
  icon?: LucideIcon;
  count?: number;
  href?: string;
  onClick?: () => void;
  active?: boolean;
}
export interface SidebarSection {
  title?: string;
  items: SidebarItem[];
  /** Small action button beside the section title, e.g. add playlist */
  action?: { icon: LucideIcon; label: string; onClick?: () => void };
  /** Takes up the remaining space, pushing later sections to the bottom */
  grow?: boolean;
}
export interface SidebarNavProps {
  sections: SidebarSection[];
  logo?: React.ReactNode;
  /** Fixed block between the logo and the scrollable nav — the account row */
  subheader?: React.ReactNode;
  /** Sits at the end of the scrollable nav content, above the footer divider */
  below?: React.ReactNode;
  /** Pinned below a divider at the very bottom — signed-in user, credits */
  footer?: React.ReactNode;
}

function SidebarLink({ item }: { item: SidebarItem }) {
  const cls = cn(
    'flex w-full cursor-pointer items-center gap-[11px] rounded-sm border-none px-3 py-[9px] text-left font-text text-sm no-underline transition-[background,color,border-color] duration-(--dur-fast) ease-(--ease-standard)',
    item.active
      ? 'bg-brand-quiet font-semibold text-heading'
      : 'bg-transparent font-normal text-muted-foreground hover:bg-surface-hover hover:text-heading',
  );
  const inner = (
    <>
      {item.icon ? <item.icon aria-hidden="true" className={cn('size-4 flex-none', item.active && 'text-brand-text')} /> : null}
      <span className="min-w-0 flex-1 truncate">{item.label}</span>
      {item.count != null ? <span className="font-mono text-2xs text-faint">{item.count}</span> : null}
    </>
  );
  if (item.href) {
    return /^[a-z]+:/.test(item.href)
      ? <a href={item.href} target="_blank" rel="noopener noreferrer" className={cls}>{inner}</a>
      : <Link href={item.href} className={cls}>{inner}</Link>;
  }
  return <button type="button" onClick={item.onClick} className={cls}>{inner}</button>;
}

export function SidebarNav({ sections = [], logo, subheader, below, footer }: SidebarNavProps) {
  return (
    <nav className="flex h-full w-(--sidebar-width) flex-none flex-col border-r border-border bg-surface-1">
      {logo ? <div className="flex-none">{logo}</div> : null}
      {subheader ? <div className="flex-none">{subheader}</div> : null}
      <div className="flex flex-1 flex-col gap-[18px] overflow-y-auto px-2 pt-3 pb-4">
        {sections.map((s, si) => (
          <div key={si} className={cn('grid content-start gap-0.5', s.grow && 'grow')}>
            {s.title ? (
              <div className="flex items-center justify-between px-3 pt-1 pb-1.5">
                <span className="text-2xs font-bold uppercase tracking-caps text-faint">{s.title}</span>
                {s.action ? <button type="button" aria-label={s.action.label} onClick={s.action.onClick} className="cursor-pointer border-none bg-transparent text-faint"><s.action.icon aria-hidden="true" className="size-3.5" /></button> : null}
              </div>
            ) : null}
            {(s.items || []).map((it) => <SidebarLink key={it.href ?? it.label} item={it} />)}
          </div>
        ))}
        {below ? <div className="mt-auto">{below}</div> : null}
      </div>
      {footer ? <div className="border-t border-border-subtle px-5 py-3">{footer}</div> : null}
    </nav>
  );
}
