import React from 'react';
import { Search } from 'lucide-react';

/** Site search input with a type-ahead popover. The popover content comes
 *  in via `panel` so the app can render real components (playable track
 *  rows, cards) rather than this component owning the result markup. */
export interface SearchBoxProps {
  value?: string;
  placeholder?: string;
  /** Rendered in the popover while the input is focused; null keeps it closed. */
  panel?: React.ReactNode;
  onChange?: React.ChangeEventHandler<HTMLInputElement>;
}

export function SearchBox({ value, onChange, placeholder = 'Search Pony.fm…', panel }: SearchBoxProps) {
  const ref = React.useRef<HTMLDivElement>(null);
  const [open, setOpen] = React.useState(false);
  const show = open && panel != null;

  // The panel can't be tied to input focus: right-clicking a track opens a
  // portaled context menu that takes focus. Instead it stays open until a
  // pointerdown lands outside both the box and any menu spawned from it.
  React.useEffect(() => {
    if (!show) return;
    const onDown = (e: PointerEvent) => {
      const t = e.target as Element | null;
      if (t && (ref.current?.contains(t) || t.closest('[data-slot^="context-menu"]'))) return;
      setOpen(false);
    };
    document.addEventListener('pointerdown', onDown);
    return () => document.removeEventListener('pointerdown', onDown);
  }, [show]);

  return (
    <div ref={ref} className="relative">
      {show ? (
        <div aria-hidden="true" onPointerDown={() => setOpen(false)}
          className="fixed inset-0 z-[890] bg-black/50 animate-in fade-in-0" />
      ) : null}
      <Search className="absolute top-1/2 left-3 z-[901] size-3.5 -translate-y-1/2 text-faint" aria-hidden="true" />
      <input value={value} placeholder={placeholder}
        onChange={(e) => { setOpen(true); onChange?.(e); }}
        onFocus={() => setOpen(true)}
        onKeyDown={(e) => { if (e.key === 'Escape') { setOpen(false); e.currentTarget.blur(); } }}
        className="relative z-[900] w-full rounded-pill border border-border bg-surface-3 py-2.5 pr-3 pl-[34px] font-text text-sm text-heading outline-none transition-[background,color,border-color,box-shadow] duration-(--dur-fast) ease-(--ease-standard) focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50" />
      {show ? (
        // Clicking any link in the panel (result rows, cards) closes it;
        // non-link actions like play/favourite keep it open.
        <div onClick={(e) => { if ((e.target as Element).closest('a')) setOpen(false); }}
          className="absolute top-[calc(100%+6px)] left-0 z-[900] max-h-[75vh] w-[min(860px,calc(100vw-88px))] overflow-y-auto rounded-md border border-border bg-surface-raised p-4 shadow-pop">
          {panel}
        </div>
      ) : null}
    </div>
  );
}
