import React from 'react';
import { cn } from '@/lib/utils';
import { Switch as UiSwitch } from '@/design-system/primitives/switch';

/** The 40x16 track / 24px knob switch carried over from forms.less,
 *  Radix-backed (role=switch, keyboard, focus ring). */
export interface SwitchProps {
    checked?: boolean;
    label?: string;
    disabled?: boolean;
    onChange?: (next: boolean) => void;
}

export function Switch({ checked, onChange, label, disabled }: SwitchProps) {
    return (
        <label className={cn('inline-flex items-center gap-2.5', disabled ? 'cursor-default opacity-50' : 'cursor-pointer')}>
            <UiSwitch checked={!!checked} disabled={disabled} onCheckedChange={(next) => onChange?.(next)} />
            {label ? <span className="text-sm text-foreground">{label}</span> : null}
        </label>
    );
}
