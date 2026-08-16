import React from 'react';
import { Check, Link2 } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Dialog } from '@/design-system/feedback/Dialog';
import { Button } from '@/design-system/core/Button';
import { Input } from '@/design-system/core/Input';

/** Brand marks — lucide no longer ships brand icons. Typed as LucideIcon so
 *  they slot into Button's icon prop. */
const XIcon = ((props) => (
    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" {...props}>
        <path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z" />
    </svg>
)) as LucideIcon;

const TumblrIcon = ((props) => (
    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" {...props}>
        <path d="M14.563 24c-5.093 0-7.031-3.756-7.031-6.411V9.747H5.116V6.648c3.63-1.313 4.512-4.596 4.71-6.469C9.84.051 9.941 0 9.999 0h3.517v6.114h4.801v3.633h-4.82v7.47c.016 1.001.375 2.371 2.207 2.371h.09c.631-.02 1.486-.205 1.936-.419l1.156 3.425c-.436.636-2.4 1.374-4.156 1.404h-.178z" />
    </svg>
)) as LucideIcon;

export interface ShareData {
    url: string;
    html?: string;
    bbcode?: string;
    twitterUrl: string;
    tumblrUrl: string;
}

/** Opens the OS share sheet where the device has one (phones, tablets).
 *  Returns false when unsupported so the caller can fall back to the dialog. */
export function shareNatively(share: ShareData, title: string): boolean {
    if (typeof navigator === 'undefined' || !navigator.share || !window.matchMedia('(pointer: coarse)').matches) {
        return false;
    }
    navigator.share({ title, url: share.url }).catch(() => undefined);
    return true;
}

export function ShareDialog({ open, onClose, title, subtitle, share }: {
    open: boolean;
    onClose: () => void;
    title: string;
    subtitle?: string;
    share: ShareData;
}) {
    const [copied, setCopied] = React.useState(false);
    const copy = () => {
        void navigator.clipboard?.writeText(share.url).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 1600);
        });
    };
    return (
        <Dialog open={open} title={'Share ' + title} subtitle={subtitle} onClose={onClose} width={520}
            footer={<>
                <Button variant="secondary" onClick={onClose}>Close</Button>
                <Button icon={copied ? Check : Link2} onClick={copy}>{copied ? 'Copied!' : 'Copy link'}</Button>
            </>}>
            <div className="grid gap-3">
                <Input label="Link" value={share.url} onChange={() => undefined} />
                {share.html ? <Input label="Embed" value={share.html} onChange={() => undefined} /> : null}
                {share.bbcode ? <Input label="BBCode" value={share.bbcode} onChange={() => undefined} /> : null}
                <div className="flex gap-2">
                    <Button size="sm" variant="secondary" icon={XIcon}
                        render={<a href={share.twitterUrl} target="_blank" rel="noopener noreferrer" />}>
                        Share on X
                    </Button>
                    <Button size="sm" variant="secondary" icon={TumblrIcon}
                        render={<a href={share.tumblrUrl} target="_blank" rel="noopener noreferrer" />}>
                        Share on Tumblr
                    </Button>
                </div>
            </div>
        </Dialog>
    );
}
