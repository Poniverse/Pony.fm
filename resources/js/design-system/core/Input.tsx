import React from 'react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Input as UiInput } from '@/design-system/primitives/input';
import { Textarea as UiTextarea } from '@/design-system/primitives/textarea';

/** Text field with the caps micro-label Pony.fm uses on every form. */
export interface InputProps {
  label?: string;
  hint?: string;
  /** Shown instead of hint, turns the label and border red */
  error?: string;
  icon?: LucideIcon;
  size?: 'md' | 'lg';
  id?: string;
  type?: string;
  value?: string;
  placeholder?: string;
  multiline?: boolean;
  rows?: number;
  onChange?: React.ChangeEventHandler<HTMLInputElement | HTMLTextAreaElement>;
}

export function Input({ label, hint, error, icon: Icon, size = 'md', value, placeholder, onChange, type = 'text', multiline, rows = 3, id }: InputProps) {
  const field = cn(
    size === 'lg' ? 'text-md' : 'text-sm',
    Icon ? 'py-2 pr-[10px] pl-[30px]' : size === 'lg' ? 'px-3 py-[11px]' : 'px-[10px] py-2',
    error ? 'border-status-danger' : 'border-border',
    multiline && 'resize-y',
  );
  return (
    <label htmlFor={id} className="block">
      {label ? <span className={cn('mb-1.5 block text-2xs font-bold uppercase tracking-caps', error ? 'text-status-danger' : 'text-muted-foreground')}>{label}</span> : null}
      <span className="relative block">
        {Icon ? <Icon aria-hidden="true" className="absolute top-1/2 left-[10px] size-4 -translate-y-1/2 text-faint" /> : null}
        {multiline
          ? <UiTextarea id={id} rows={rows} value={value} placeholder={placeholder} onChange={onChange} className={field} />
          : <UiInput id={id} type={type} value={value} placeholder={placeholder} onChange={onChange} className={field} />}
      </span>
      {error ? <span className="mt-[5px] block text-2xs text-status-danger">{error}</span>
        : hint ? <span className="mt-[5px] block text-2xs text-faint">{hint}</span> : null}
    </label>
  );
}
