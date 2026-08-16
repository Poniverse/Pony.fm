import React from 'react';
import { Download } from 'lucide-react';
import { Button } from '@/design-system/core/Button';
import { Dialog } from '@/design-system/feedback/Dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/design-system/primitives/dropdown-menu';
import { api } from '@/lib/api';

export interface DownloadFormat {
    name: string;
    extension: string;
    url: string;
    size: string;
    isCacheable: boolean;
    isMixedLosslessness?: boolean;
}

/**
 * Download dropdown with per-format sizes. Formats that are transcoded on
 * demand (OGG/ALAC/AAC) return {url: null} until an encode job finishes, so
 * those are polled every 5 seconds — same flow as the old download-cached
 * service.
 */
export function DownloadMenu({ formats, resourceType, resourceId, shouldConfirm }: {
    formats: DownloadFormat[];
    resourceType: 'tracks' | 'albums' | 'playlists';
    resourceId: number;
    /** Return a warning message to require confirmation before this format downloads. */
    shouldConfirm?: (f: DownloadFormat) => string | null;
}) {
    const [pending, setPending] = React.useState<string | null>(null);
    const [confirming, setConfirming] = React.useState<{ format: DownloadFormat; message: string } | null>(null);
    const timer = React.useRef<ReturnType<typeof setTimeout>>(undefined);

    React.useEffect(() => () => clearTimeout(timer.current), []);

    const pick = (format: DownloadFormat) => {
        const message = shouldConfirm?.(format);
        if (message) {
            setConfirming({ format, message });
            return;
        }
        download(format);
    };

    const download = (format: DownloadFormat) => {
        if (!format.isCacheable) {
            window.location.href = format.url;
            return;
        }
        setPending(format.name);
        const poll = () => {
            api.get<{ url: string | null }>(`/${resourceType}/cached/${resourceId}/${encodeURIComponent(format.name)}`)
                .then(({ data }) => {
                    if (data.url) {
                        setPending(null);
                        window.location.href = data.url;
                    } else {
                        timer.current = setTimeout(poll, 5000);
                    }
                })
                .catch(() => setPending(null));
        };
        poll();
    };

    if (!formats.length) return null;

    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger render={<Button variant="secondary" icon={Download} loading={pending != null} />}>
                    {pending ? 'Preparing ' + pending + '…' : 'Download'}
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start" className="min-w-[230px]">
                    {formats.map((f) => (
                        <DropdownMenuItem key={f.name} onClick={() => pick(f)} className="flex items-baseline gap-2.5">
                            <span className="flex-1 text-heading">{f.name}</span>
                            <span className="font-mono text-2xs text-faint">{f.size}</span>
                        </DropdownMenuItem>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>
            <Dialog open={confirming != null} title="Before you download…" onClose={() => setConfirming(null)}
                footer={<>
                    <Button variant="secondary" onClick={() => setConfirming(null)}>Cancel</Button>
                    <Button icon={Download} onClick={() => { if (confirming) download(confirming.format); setConfirming(null); }}>Download anyway</Button>
                </>}>
                {confirming?.message}
            </Dialog>
        </>
    );
}
