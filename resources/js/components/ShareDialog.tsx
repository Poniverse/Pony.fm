import React from 'react';
import { Check, Link2 } from 'lucide-react';
import { Dialog } from '@/design-system/feedback/Dialog';
import { Button } from '@/design-system/core/Button';
import { Input } from '@/design-system/core/Input';

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
                    <Button size="sm" variant="secondary" onClick={() => window.open(share.twitterUrl, '_blank', 'noopener')}>Share on Twitter</Button>
                    <Button size="sm" variant="secondary" onClick={() => window.open(share.tumblrUrl, '_blank', 'noopener')}>Share on Tumblr</Button>
                </div>
            </div>
        </Dialog>
    );
}
