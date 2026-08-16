import React from 'react';
import { X } from 'lucide-react';
import { MultiCombobox } from '@/design-system/core/Combobox';
import { cn } from '@/lib/utils';

/**
 * Row of multi-select filter comboboxes above a listing — genre, year,
 * licence. Each opens a typeahead panel; an engaged filter turns purple and
 * grows a clear button, as on the live site.
 */
export interface FilterSpec { id: string; label: string; options: string[]; selected?: string[] }
export interface FilterBarProps {
  filters: FilterSpec[];
  onToggle?: (filterId: string, option: string) => void;
  onClear?: (filterId: string) => void;
  right?: React.ReactNode;
}

function Filter({ filter, onToggle, onClear }: { filter: FilterSpec; onToggle?: FilterBarProps['onToggle']; onClear?: FilterBarProps['onClear'] }) {
  const activeCount = (filter.selected || []).length;
  const on = activeCount > 0;
  return (
    <div className="inline-flex">
      <MultiCombobox
        options={filter.options || []}
        selected={filter.selected}
        onToggle={(o) => onToggle && onToggle(filter.id, o)}
        placeholder={'Search ' + filter.label.toLowerCase() + '…'}
      >
        <button type="button"
          className={cn(
            'inline-flex cursor-pointer items-center gap-[7px] border px-3.5 py-1.5 font-text text-xs font-semibold transition-[background,color,border-color] duration-(--dur-fast) ease-(--ease-standard)',
            on ? 'border-transparent bg-primary text-primary-foreground' : 'border-border bg-surface-3 text-foreground',
            on && onClear ? 'rounded-l-sm rounded-r-none' : 'rounded-sm',
          )}>
          {filter.label}{on ? ' · ' + activeCount : ''}
        </button>
      </MultiCombobox>
      {on && onClear ? (
        <button type="button" aria-label={'Clear ' + filter.label} onClick={() => onClear(filter.id)}
          className="cursor-pointer rounded-l-none rounded-r-sm border-none bg-purple-700 py-1.5 pr-3 pl-2 text-xs text-white">
          <X className="inline size-3" aria-hidden="true" />
        </button>
      ) : null}
    </div>
  );
}

export function FilterBar({ filters = [], onToggle, onClear, right }: FilterBarProps) {
  return (
    <div className="flex flex-wrap items-center gap-2">
      {filters.map((fl) => <Filter key={fl.id} filter={fl} onToggle={onToggle} onClear={onClear} />)}
      {right ? <div className="ml-auto">{right}</div> : null}
    </div>
  );
}
