import React from 'react';
import type { VariantProps } from 'class-variance-authority';
import type { LucideIcon } from 'lucide-react';
import { Badge as UiBadge, badgeVariants } from '@/design-system/primitives/badge';

/** Small caps label for track attributes: lossless, instrumental, explicit, WIP. */
export interface BadgeProps extends VariantProps<typeof badgeVariants> {
    icon?: LucideIcon;
    children?: React.ReactNode;
}

export function Badge({ tone, icon: Icon, children, uppercase }: BadgeProps) {
    return (
        <UiBadge tone={tone} uppercase={uppercase}>
            {Icon ? <Icon aria-hidden="true" className="size-[1.2em]" /> : null}
            {children}
        </UiBadge>
    );
}
