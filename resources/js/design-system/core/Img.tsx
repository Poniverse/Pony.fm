import React from 'react';
import { cn } from '@/lib/utils';

/**
 * An <img> that stays invisible until the file has actually loaded, then
 * fades in. Whatever sits behind it (surface colour, icon placeholder)
 * shows until then, so images never render half-painted. A failed load
 * renders nothing, leaving the placeholder visible.
 */
export function Img({ className, src, onLoad, onError, ...rest }: React.ComponentPropsWithRef<'img'>) {
    const ref = React.useRef<HTMLImageElement | null>(null);
    const [loaded, setLoaded] = React.useState(false);
    const [failed, setFailed] = React.useState(false);

    // Reset when the source changes, and catch images that completed from
    // cache before hydration attached the load handler.
    React.useEffect(() => {
        setFailed(false);
        setLoaded(!!ref.current?.complete && ref.current.naturalWidth > 0);
    }, [src]);

    if (failed) return null;

    return (
        <img
            ref={ref}
            src={src}
            onLoad={(e) => { setLoaded(true); onLoad?.(e); }}
            onError={(e) => { setFailed(true); onError?.(e); }}
            className={cn(
                'transition-opacity duration-(--dur-normal) ease-(--ease-standard)',
                loaded ? 'opacity-100' : 'opacity-0',
                className,
            )}
            {...rest}
        />
    );
}
