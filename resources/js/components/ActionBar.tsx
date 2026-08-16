import React from 'react';
import { cn } from '@/lib/utils';

/**
 * A single-row action strip that scrolls horizontally instead of wrapping —
 * swipeable on touch, drag-to-scroll with a mouse, with gradient fades on
 * either edge hinting at off-screen actions. Clicks are suppressed when the
 * pointer actually dragged.
 */
export function ActionBar({ children, className }: { children: React.ReactNode; className?: string }) {
    const ref = React.useRef<HTMLDivElement>(null);
    const drag = React.useRef<{ startX: number; startScroll: number; moved: boolean } | null>(null);
    const [fades, setFades] = React.useState({ left: false, right: false });

    const updateFades = React.useCallback(() => {
        const el = ref.current;
        if (!el) return;
        const left = el.scrollLeft > 2;
        const right = el.scrollLeft < el.scrollWidth - el.clientWidth - 2;
        setFades((f) => (f.left === left && f.right === right ? f : { left, right }));
    }, []);

    React.useEffect(() => {
        updateFades();
        const el = ref.current;
        if (!el) return;
        const observer = new ResizeObserver(updateFades);
        observer.observe(el);
        window.addEventListener('resize', updateFades);
        return () => {
            observer.disconnect();
            window.removeEventListener('resize', updateFades);
        };
    }, [updateFades]);

    const onPointerDown = (e: React.PointerEvent) => {
        if (e.pointerType !== 'mouse' || !ref.current) return;
        drag.current = { startX: e.clientX, startScroll: ref.current.scrollLeft, moved: false };
    };

    const onPointerMove = (e: React.PointerEvent) => {
        if (!drag.current || !ref.current) return;
        const delta = e.clientX - drag.current.startX;
        if (Math.abs(delta) > 5) drag.current.moved = true;
        if (drag.current.moved) ref.current.scrollLeft = drag.current.startScroll - delta;
    };

    const onPointerUp = () => {
        // Keep the moved flag through the click that follows pointerup.
        if (drag.current && !drag.current.moved) drag.current = null;
    };

    const onClickCapture = (e: React.MouseEvent) => {
        if (drag.current?.moved) {
            e.preventDefault();
            e.stopPropagation();
        }
        drag.current = null;
    };

    return (
        <div className={cn('relative min-w-0', className)}>
            <div
                ref={ref}
                onScroll={updateFades}
                onPointerDown={onPointerDown}
                onPointerMove={onPointerMove}
                onPointerUp={onPointerUp}
                onPointerLeave={onPointerUp}
                onClickCapture={onClickCapture}
                className={cn(
                    // -m-1/p-1 keeps 4px inside the scrollport so the focus ring isn't clipped.
                    'flex select-none gap-2 overflow-x-auto whitespace-nowrap -m-1 p-1',
                    '[-webkit-overflow-scrolling:touch] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden',
                    '[&>*]:flex-none',
                )}
            >
                {children}
            </div>
            <div aria-hidden="true" className={cn(
                'pointer-events-none absolute -inset-y-1 -left-1 w-10 bg-linear-to-r from-background to-transparent transition-opacity duration-(--dur-fast)',
                fades.left ? 'opacity-100' : 'opacity-0',
            )} />
            <div aria-hidden="true" className={cn(
                'pointer-events-none absolute -inset-y-1 -right-1 w-10 bg-linear-to-l from-background to-transparent transition-opacity duration-(--dur-fast)',
                fades.right ? 'opacity-100' : 'opacity-0',
            )} />
        </div>
    );
}
