import React from 'react';
import { X } from 'lucide-react';
import {
    Popover as UiPopover,
    PopoverAnchor,
    PopoverContent,
} from '@/design-system/primitives/popover';
import { IconButton } from '../core/IconButton';

const PLACE: Record<string, { side: 'top' | 'right' | 'bottom' | 'left'; align: 'start' | 'center' | 'end' }> = {
    right: { side: 'right', align: 'end' },
    'right-top': { side: 'right', align: 'start' },
    below: { side: 'bottom', align: 'end' },
    above: { side: 'top', align: 'start' },
};

/**
 * Floating panel anchored to the control that opened it. Give the trigger's
 * wrapper position: relative and render this beside the trigger — the anchor
 * element fills that wrapper. Outside-click, ESC and focus handling come from
 * the shadcn popover primitive.
 */
export interface PopoverProps {
    open?: boolean;
    title?: string;
    /** Where it sits relative to the anchor */
    placement?: 'right' | 'right-top' | 'below' | 'above';
    width?: number;
    footer?: React.ReactNode;
    onClose?: () => void;
    children?: React.ReactNode;
}

export function Popover({ open, title, placement = 'right', width = 320, onClose, children, footer }: PopoverProps) {
    const { side, align } = PLACE[placement] ?? PLACE.right;
    const anchorRef = React.useRef<HTMLDivElement>(null);
    return (
        <UiPopover open={!!open} onOpenChange={(next) => { if (!next) onClose?.(); }}>
            <PopoverAnchor ref={anchorRef} className="pointer-events-none absolute inset-0" aria-hidden="true" />
            <PopoverContent
                anchor={anchorRef}
                side={side}
                align={align}
                sideOffset={10}
                style={{ width }}
                className="flex max-h-[420px] flex-col overflow-hidden rounded-md bg-surface-raised p-0 shadow-pop"
            >
                {title ? (
                    <div className="flex items-center gap-2 border-b border-border-subtle px-3 py-[11px]">
                        <span className="flex-1 text-2xs font-bold uppercase tracking-caps text-faint">{title}</span>
                        {onClose ? <IconButton icon={X} label="Close" size="sm" onClick={onClose} /> : null}
                    </div>
                ) : null}
                <div className="min-h-0 flex-1 overflow-y-auto p-2.5">{children}</div>
                {footer ? <div className="border-t border-border-subtle px-3 py-2.5 text-2xs text-faint">{footer}</div> : null}
            </PopoverContent>
        </UiPopover>
    );
}
