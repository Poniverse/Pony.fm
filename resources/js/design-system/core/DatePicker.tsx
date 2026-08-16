import React from 'react';
import { format, parse, isValid } from 'date-fns';
import { Calendar as CalendarIcon } from 'lucide-react';
import { Calendar } from '@/design-system/primitives/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/design-system/primitives/popover';
import { cn } from '@/lib/utils';

const labelClass = 'mb-1.5 block text-2xs font-bold uppercase tracking-caps';

const triggerClass = (error?: string) => cn(
    'flex w-full cursor-pointer items-center gap-2 rounded-control border bg-surface-3 px-2.5 py-2 text-left font-text text-sm text-heading outline-none transition-[background,color,border-color,box-shadow] duration-(--dur-fast) ease-(--ease-standard) focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
    error ? 'border-status-danger' : 'border-border',
);

function Field({ label, error, hint, children }: { label?: string; error?: string; hint?: string; children: React.ReactNode }) {
    return (
        <div>
            {label ? <span className={cn(labelClass, error ? 'text-status-danger' : 'text-muted-foreground')}>{label}</span> : null}
            {children}
            {error ? <span className="mt-[5px] block text-2xs text-status-danger">{error}</span>
                : hint ? <span className="mt-[5px] block text-2xs text-faint">{hint}</span> : null}
        </div>
    );
}

/** Calendar-popover date field. Value round-trips as '' or 'YYYY-MM-DD'. */
export function DatePicker({ label, value, onChange, error, hint, placeholder = 'Pick a date' }: {
    label?: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    hint?: string;
    placeholder?: string;
}) {
    const [open, setOpen] = React.useState(false);
    const date = value ? parse(value, 'yyyy-MM-dd', new Date()) : undefined;
    const selected = date && isValid(date) ? date : undefined;

    return (
        <Field label={label} error={error} hint={hint}>
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger render={<button type="button" className={triggerClass(error)} />}>
                    <CalendarIcon className="size-4 text-faint" aria-hidden="true" />
                    {selected ? format(selected, 'PPP') : <span className="text-faint">{placeholder}</span>}
                </PopoverTrigger>
                <PopoverContent align="start" className="w-auto p-0">
                    <Calendar
                        mode="single"
                        selected={selected}
                        defaultMonth={selected}
                        captionLayout="dropdown"
                        onSelect={(next) => {
                            onChange(next ? format(next, 'yyyy-MM-dd') : '');
                            setOpen(false);
                        }}
                    />
                </PopoverContent>
            </Popover>
        </Field>
    );
}

/** Date + time field. Value round-trips as '' or 'YYYY-MM-DDTHH:mm' (local). */
export function DateTimePicker({ label, value, onChange, error, hint, placeholder = 'Pick a date' }: {
    label?: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    hint?: string;
    placeholder?: string;
}) {
    const [open, setOpen] = React.useState(false);
    const parsed = value ? parse(value, "yyyy-MM-dd'T'HH:mm", new Date()) : undefined;
    const selected = parsed && isValid(parsed) ? parsed : undefined;
    const time = value.includes('T') ? value.split('T')[1] : '00:00';

    return (
        <Field label={label} error={error} hint={hint}>
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger render={<button type="button" className={triggerClass(error)} />}>
                    <CalendarIcon className="size-4 text-faint" aria-hidden="true" />
                    {selected ? format(selected, 'PPP, HH:mm') : <span className="text-faint">{placeholder}</span>}
                </PopoverTrigger>
                <PopoverContent align="start" className="w-auto p-0">
                    <Calendar
                        mode="single"
                        selected={selected}
                        defaultMonth={selected}
                        captionLayout="dropdown"
                        onSelect={(next) => {
                            if (!next) return onChange('');
                            onChange(format(next, 'yyyy-MM-dd') + 'T' + time);
                        }}
                    />
                    <div className="flex items-center gap-2 border-t border-border-subtle px-3 py-2.5">
                        <span className="text-2xs font-bold uppercase tracking-caps text-muted-foreground">Time</span>
                        <input
                            type="time"
                            value={time}
                            disabled={!selected}
                            onChange={(e) => {
                                if (selected) onChange(format(selected, 'yyyy-MM-dd') + 'T' + (e.target.value || '00:00'));
                            }}
                            className="rounded-control border border-border bg-surface-3 px-2 py-1 font-mono text-sm text-heading outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-50"
                        />
                    </div>
                </PopoverContent>
            </Popover>
        </Field>
    );
}
