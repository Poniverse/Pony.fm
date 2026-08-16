import React from 'react';
import { cn } from '@/lib/utils';
import { Avatar as UiAvatar, AvatarFallback, AvatarImage } from '@/design-system/primitives/avatar';

const S = { xs: 24, sm: 32, md: 40, lg: 56, xl: 120 } as const;

/** Round user avatar with an initial fallback. Radix-backed: the fallback
 *  shows automatically while the image loads or if it errors. */
export interface AvatarProps {
    src?: string;
    name?: string;
    size?: keyof typeof S;
    /** Square corners, for artist header art */
    square?: boolean;
}

export function Avatar({ src, name = '', size = 'md', square }: AvatarProps) {
    const px = S[size] || S.md;
    return (
        <UiAvatar
            className={cn('flex-none shadow-(--ring-inset)', square ? 'rounded-art' : 'rounded-avatar')}
            style={{ width: px, height: px }}
        >
            {src ? <AvatarImage src={src} alt={name} className="object-cover" /> : null}
            <AvatarFallback
                className={cn('bg-surface-3 font-display font-semibold text-muted-foreground', square ? 'rounded-art' : 'rounded-avatar')}
                style={{ fontSize: Math.round(px * 0.42) }}
            >
                {name.slice(0, 1).toUpperCase()}
            </AvatarFallback>
        </UiAvatar>
    );
}
