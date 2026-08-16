import React from 'react';
import { Button as ButtonPrimitive } from '@base-ui/react/button';
import { cva, type VariantProps } from 'class-variance-authority';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/design-system/primitives/tooltip';

const iconButton = cva(
    'inline-flex cursor-pointer items-center justify-center rounded-control border border-transparent bg-transparent text-muted-foreground no-underline transition-[background,color,border-color] duration-(--dur-fast) ease-(--ease-standard) not-disabled:hover:bg-surface-hover disabled:cursor-default disabled:text-faint disabled:opacity-50',
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

/** A single icon as a button — player transport, row actions, toolbars.
 *  Compose with `render` to render something else, e.g. a real link:
 *  `<IconButton icon={Pencil} label="Edit" render={<Link href="…" />} />`. */
export interface IconButtonProps
    extends VariantProps<typeof iconButton>,
        Omit<ButtonPrimitive.Props, 'className' | 'type'> {
    icon: LucideIcon;
    /** Accessible label, also the tooltip */
    label: string;
    className?: string;
}

export function IconButton({ icon: Icon, label, size, variant, active, round, className, render, nativeButton, ...rest }: IconButtonProps) {
    return (
        <Tooltip>
            <TooltipTrigger render={
                <ButtonPrimitive
                    render={render}
                    nativeButton={nativeButton ?? render == null}
                    type={render ? undefined : 'button'}
                    aria-label={label}
                    className={cn(iconButton({ size, variant, round, active }), className)}
                    {...rest}
                >
                    <Icon aria-hidden="true" className="size-[1.2em]" />
                </ButtonPrimitive>
            } />
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
}
