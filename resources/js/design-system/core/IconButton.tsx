import React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

const iconButton = cva(
    'inline-flex cursor-pointer items-center justify-center rounded-control border border-transparent bg-transparent text-muted-foreground transition-[background,color,border-color] duration-(--dur-fast) ease-(--ease-standard) enabled:hover:bg-surface-hover disabled:cursor-default disabled:text-faint disabled:opacity-50',
    {
        variants: {
            size: {
                sm: 'size-7 text-sm',
                md: 'size-[34px] text-sm',
                lg: 'size-11 text-lg',
            },
            variant: {
                ghost: null,
                filled: 'bg-primary text-primary-foreground enabled:hover:bg-(--brand-solid-hover)',
            },
            round: { true: 'rounded-pill' },
            active: { true: 'bg-surface-hover text-heading' },
        },
        defaultVariants: {
            size: 'md',
            variant: 'ghost',
        },
    },
);

/** A single icon as a button — player transport, row actions, toolbars. */
export interface IconButtonProps
    extends VariantProps<typeof iconButton>,
        Omit<React.ComponentPropsWithRef<'button'>, 'type'> {
    icon: LucideIcon;
    /** Accessible label, also the tooltip */
    label: string;
}

export function IconButton({ icon: Icon, label, size, variant, active, round, className, ...rest }: IconButtonProps) {
    return (
        <button type="button" title={label} aria-label={label}
            className={cn(iconButton({ size, variant, round, active }), className)} {...rest}>
            <Icon aria-hidden="true" className="size-[1.2em]" />
        </button>
    );
}
