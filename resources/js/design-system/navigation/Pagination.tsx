import React from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import {
  Pagination as UiPagination,
  PaginationContent,
  PaginationItem,
} from '@/design-system/primitives/pagination';

/** Page strip for long listings. The brand fill is a single indicator that
 *  slides to the current page rather than fading between buttons. */
export interface PaginationProps { page?: number; pages?: number; onChange?: (page: number) => void }

export function Pagination({ page = 1, pages = 1, onChange }: PaginationProps) {
  const contentRef = React.useRef<HTMLUListElement>(null);
  // The indicator moves as soon as a page is clicked; the content catches up.
  const [pending, setPending] = React.useState<number | null>(null);
  React.useEffect(() => setPending(null), [page]);
  const active = pending ?? page;
  const [indicator, setIndicator] = React.useState<{ left: number; width: number } | null>(null);
  const animateRef = React.useRef(false);

  const measure = React.useCallback(() => {
    const content = contentRef.current;
    const active = content?.querySelector<HTMLButtonElement>('[aria-current="page"]');
    if (!content || !active) {
      setIndicator(null);
      return;
    }
    const box = content.getBoundingClientRect();
    const btn = active.getBoundingClientRect();
    setIndicator({ left: btn.left - box.left, width: btn.width });
  }, []);

  React.useLayoutEffect(() => {
    measure();
    // Skip the slide on first paint; every later move animates.
    const raf = requestAnimationFrame(() => { animateRef.current = true; });
    return () => cancelAnimationFrame(raf);
  }, [measure, active, pages]);

  React.useEffect(() => {
    const content = contentRef.current;
    if (!content) return;
    const observer = new ResizeObserver(measure);
    observer.observe(content);
    return () => observer.disconnect();
  }, [measure]);

  const list: number[] = [];
  const from = Math.max(1, Math.min(page - 2, pages - 4));
  const to = Math.min(pages, from + 4);
  for (let i = from; i <= to; i++) list.push(i);
  const btn = (content: React.ReactNode, target: number, on: boolean, key: React.Key) => (
    <PaginationItem key={key}>
      <button type="button" disabled={!target} onClick={() => { if (target && onChange) { setPending(target); onChange(target); } }}
        aria-current={on ? 'page' : undefined}
        className={cn(
          'relative z-10 h-[30px] min-w-8 rounded-sm border-none bg-transparent px-[9px] font-mono text-xs transition-[color] duration-(--dur-fast) ease-(--ease-standard)',
          on ? 'text-primary-foreground' : target ? 'text-foreground' : 'text-faint',
          target && !on ? 'cursor-pointer' : 'cursor-default',
        )}>{content}</button>
    </PaginationItem>
  );
  return (
    <UiPagination className="rounded-md border border-border-subtle bg-surface-2 p-1">
      <PaginationContent ref={contentRef} className="relative gap-[3px]">
        {indicator ? (
          <span aria-hidden="true"
            className={cn(
              'absolute top-0 h-[30px] rounded-sm bg-primary',
              animateRef.current && 'transition-[left,width] duration-[420ms] ease-(--ease-out)',
            )}
            style={{ left: indicator.left, width: indicator.width }} />
        ) : null}
        {btn(<ChevronLeft className="inline size-3" aria-hidden="true" />, active > 1 ? active - 1 : 0, false, 'prev')}
        {from > 1 ? btn('1', 1, active === 1, 'first') : null}
        {from > 2 ? <PaginationItem key="e1"><span className="px-[3px] text-faint">…</span></PaginationItem> : null}
        {list.map((p) => btn(p, p, p === active, p))}
        {to < pages - 1 ? <PaginationItem key="e2"><span className="px-[3px] text-faint">…</span></PaginationItem> : null}
        {to < pages ? btn(pages, pages, active === pages, 'last') : null}
        {btn(<ChevronRight className="inline size-3" aria-hidden="true" />, active < pages ? active + 1 : 0, false, 'next')}
      </PaginationContent>
    </UiPagination>
  );
}
