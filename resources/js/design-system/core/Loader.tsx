import React from 'react';
import { cn } from '@/lib/utils';

/** The original Pony.fm loading gif, for indeterminate waits — downloads
 *  being prepared, uploads processing, panels fetching. Spinner glyphs are
 *  for buttons only when the gif would be too heavy; default to this. */
export function Loader({ size = 16, label = 'Loading…', className }: {
    /** Pixel size (square). */
    size?: number;
    label?: string;
    className?: string;
}) {
    return (
        <img
            src="/images/loading.gif"
            alt={label}
            title={label}
            width={size}
            height={size}
            className={cn('inline-block flex-none', className)}
        />
    );
}
