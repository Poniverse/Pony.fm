import React from 'react';
import {
    Select as UiSelect,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/design-system/primitives/select';

/** Select with the caps micro-label. Base UI-backed (typeahead, keyboard nav,
 *  aria) while keeping the native-select-style onChange API. Callers' ''
 *  values are mapped to null at this boundary: Base UI treats a value
 *  serializing to '' as "empty", and its list-change cleanup resets any
 *  non-null value it can't find among the current items — so an ''-valued
 *  item that is conditionally removed after selection (TrackEditor's
 *  "Choose a…" rows) would bounce the select straight back to empty. A
 *  null item's label doubles as the trigger placeholder. */
export interface SelectOption { value: string; label: string }
export interface SelectProps {
    label?: string;
    id?: string;
    value?: string;
    options?: (string | SelectOption)[];
    onChange?: React.ChangeEventHandler<HTMLSelectElement>;
}

export function Select({ label, options = [], value, onChange, id }: SelectProps) {
    const items = options.map((o) => {
        const { value: v, label: l } = typeof o === 'string' ? { value: o, label: o } : o;
        return { value: v === '' ? null : v, label: l };
    });
    const emit = (next: string | null) => {
        onChange?.({
            target: { value: next ?? '' },
        } as unknown as React.ChangeEvent<HTMLSelectElement>);
    };
    return (
        <label htmlFor={id} className="block">
            {label ? <span className="mb-1.5 block text-2xs font-bold uppercase tracking-caps text-muted-foreground">{label}</span> : null}
            <UiSelect value={value === '' ? null : value} onValueChange={emit} items={items}>
                <SelectTrigger id={id} className="w-full rounded-control border-border bg-surface-3 font-text text-sm text-heading">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent className="rounded-control border-border bg-surface-raised">
                    {items.map((o) => (
                        <SelectItem key={o.value ?? ''} value={o.value} className="font-text text-sm">
                            {o.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </UiSelect>
        </label>
    );
}
