import React from 'react';
import {
    Dialog as UiDialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/design-system/primitives/dialog';

/** Modal dialog — share, add to playlist, delete confirmation, credits.
 *  Radix-backed: focus trapping, ESC/outside-click and aria come from the
 *  shadcn dialog primitive underneath. */
export interface DialogProps {
    open?: boolean;
    title?: string;
    subtitle?: string;
    width?: number;
    footer?: React.ReactNode;
    onClose?: () => void;
    children?: React.ReactNode;
}

export function Dialog({ open = true, title, subtitle, children, footer, onClose, width = 460 }: DialogProps) {
    return (
        <UiDialog open={open} onOpenChange={(next) => { if (!next) onClose?.(); }}>
            <DialogContent
                showCloseButton={!!onClose}
                style={{ width, maxWidth: '92vw' }}
                className="gap-0 rounded-card bg-surface-raised p-0 sm:max-w-none"
            >
                <DialogHeader className="border-b border-border-subtle px-[18px] pt-4 pb-3 text-left">
                    <DialogTitle className="font-display text-xl font-semibold tracking-display text-heading">{title}</DialogTitle>
                    {subtitle ? <DialogDescription className="mt-[3px] text-xs text-muted-foreground">{subtitle}</DialogDescription> : null}
                </DialogHeader>
                <div className="max-h-[70vh] overflow-y-auto px-[18px] py-4 text-sm text-foreground">{children}</div>
                {footer ? (
                    <DialogFooter className="flex-row justify-end gap-2 border-t border-border-subtle px-[18px] pt-3 pb-4">{footer}</DialogFooter>
                ) : null}
            </DialogContent>
        </UiDialog>
    );
}
