import React from 'react';
import { LayoutGrid, Music, Search, User } from 'lucide-react';

/** Site search with a type-ahead result popover. */
export interface SearchResult { title: string; kind: 'track' | 'album' | 'artist' | 'playlist' }
export interface SearchBoxProps {
  value?: string;
  placeholder?: string;
  results?: SearchResult[];
  onChange?: React.ChangeEventHandler<HTMLInputElement>;
  onPick?: (r: SearchResult) => void;
}

export function SearchBox({ value, onChange, placeholder = 'Search Pony.fm…', results = [], onPick }: SearchBoxProps) {
  const [focus, setFocus] = React.useState(false);
  const open = focus && results.length > 0;
  return (
    <div className="relative">
      <Search className="absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-faint" aria-hidden="true" />
      <input value={value} placeholder={placeholder} onChange={onChange}
        onFocus={() => setFocus(true)} onBlur={() => setTimeout(() => setFocus(false), 120)}
        className="w-full rounded-pill border border-border bg-surface-3 py-2.5 pr-3 pl-[34px] font-text text-sm text-heading outline-none transition-[background,color,border-color,box-shadow] duration-(--dur-fast) ease-(--ease-standard) focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50" />
      {open ? (
        <div className="absolute top-[calc(100%+6px)] right-0 left-0 z-[900] overflow-hidden rounded-md border border-border bg-surface-raised shadow-pop">
          {results.map((r, i) => (
            <button key={i} type="button" onClick={() => onPick && onPick(r)}
              className="flex w-full cursor-pointer items-center gap-2.5 border-none bg-transparent px-3 py-[9px] text-left font-text text-sm text-foreground hover:bg-surface-hover">
              {(() => {
                const Icon = r.kind === 'artist' ? User : r.kind === 'album' ? LayoutGrid : Music;
                return <Icon className="size-4 flex-none text-faint" aria-hidden="true" />;
              })()}
              <span className="min-w-0 flex-1 truncate text-heading">{r.title}</span>
              <span className="text-2xs uppercase tracking-caps text-faint">{r.kind}</span>
            </button>
          ))}
        </div>
      ) : null}
    </div>
  );
}
