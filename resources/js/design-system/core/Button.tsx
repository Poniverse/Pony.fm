import React from 'react';
import { Button as ButtonPrimitive } from '@base-ui/react/button';
import type { LucideIcon } from 'lucide-react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';
import { Loader } from './Loader';

const button = cva(
    'inline-flex cursor-pointer items-center justify-center gap-[7px] rounded-control border border-transparent font-text font-semibold tracking-[0.01em] no-underline transition-[background,color,border-color,transform,filter] duration-(--dur-fast) ease-(--ease-standard) not-disabled:hover:brightness-[1.14] not-disabled:active:scale-[0.97] disabled:cursor-default disabled:opacity-45',
    {
        variants: {
            variant: {
                primary: 'bg-primary text-primary-foreground',
                secondary: 'border-border bg-surface-3 text-heading',
                quiet: 'bg-brand-quiet text-brand-text',
                ghost: 'bg-transparent text-muted-foreground',
                danger: 'bg-destructive text-white',
            },
            size: {
                sm: 'px-[10px] py-[5px] text-xs',
                md: 'px-[14px] py-2 text-sm',
                lg: 'min-h-(--hit-min) px-5 py-[11px] text-md',
            },
            block: { true: 'flex w-full' },
            active: { true: 'shadow-(--ring-inset)' },
        },
        compoundVariants: [
            { variant: 'ghost', active: true, class: 'bg-surface-active text-heading' },
        ],
        defaultVariants: {
            variant: 'primary',
            size: 'md',
        },
    },
);

/** Pony.fm's text button. Square-ish corners, purple fill for the primary action.
 *  Spreads native button props (and ref) so Base UI render composition works.
 *
 *  Compose with `render` to render something else as a button — e.g. a real
 *  link: `<Button render={<Link href="/tracks" />} icon={Music}>Browse</Button>`.
 *  When `render` is given, `nativeButton` defaults to false (the usual target
 *  here is an anchor); pass `nativeButton` explicitly if rendering a button. */
export interface ButtonProps
    extends VariantProps<typeof button>,
        Omit<ButtonPrimitive.Props, 'className' | 'type'> {
    icon?: LucideIcon;
    iconRight?: LucideIcon;
    /** Replaces the icon with the classic loading gif while an async wait runs. */
    loading?: boolean;
    className?: string;
    type?: 'button' | 'submit';
}

export function Button({ variant, size, icon: Icon, iconRight: IconRight, loading, render, nativeButton, active, block, children, className, type = 'button', ...rest }: ButtonProps) {
    return (
        <ButtonPrimitive
            render={render}
            nativeButton={nativeButton ?? render == null}
            type={render ? undefined : type}
            className={cn(button({ variant, size, block, active }), className)}
            {...rest}
        >
            {loading ? <Loader size={14} /> : Icon ? <Icon aria-hidden="true" className="size-[1.15em]" /> : null}
            {children}
            {IconRight ? <IconRight aria-hidden="true" className="size-[1.15em]" /> : null}
        </ButtonPrimitive>
    );
}
