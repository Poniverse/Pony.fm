import React from 'react';
import { Download } from 'lucide-react';
import { Button } from '@/design-system/core/Button';
import { IconButton } from '@/design-system/core/IconButton';
import { toast } from '@/design-system/primitives/toast';
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
export function DownloadMenu({ formats, resourceType, resourceId, shouldConfirm, iconOnly, iconRound, iconSize }: {
    formats: DownloadFormat[];
    resourceType: 'tracks' | 'albums' | 'playlists';
    resourceId: number;
    /** Return a warning message to require confirmation before this format downloads. */
    shouldConfirm?: (f: DownloadFormat) => string | null;
    /** Renders as a bare icon button for compact headers. */
    iconOnly?: boolean;
    /** Icon-only styling: round shape and size, e.g. to match a round play button. */
    iconRound?: boolean;
    iconSize?: 'sm' | 'md' | 'lg';
}) {
    const [pending, setPending] = React.useState<string | null>(null);
    const [confirming, setConfirming] = React.useState<{ format: DownloadFormat; message: string } | null>(null);
    // Deliberately not cleared on unmount: the progress toast outlives this
    // page (the toast manager is global), so polling must too — the download
    // still starts if the user navigates away while the encode runs.
    const timer = React.useRef<ReturnType<typeof setTimeout>>(undefined);

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
            toast.add({ type: 'success', title: 'Download started', description: format.name + ' · ' + format.size });
            window.location.href = format.url;
            return;
        }
        setPending(format.name);
        const ready = new Promise<string>((resolve, reject) => {
            const poll = () => {
                api.get<{ url: string | null }>(`/${resourceType}/cached/${resourceId}/${encodeURIComponent(format.name)}`)
                    .then(({ data }) => {
                        if (data.url) {
                            resolve(data.url);
                        } else {
                            timer.current = setTimeout(poll, 5000);
                        }
                    })
                    .catch(reject);
            };
            poll();
        });
        void toast.promise(ready, {
            loading: { title: 'Preparing your download…', description: format.name + ' is being encoded.' },
            success: { title: 'Download started', description: format.name + ' · ' + format.size },
            error: { title: 'Download failed', description: 'Something went wrong preparing ' + format.name + '. Please try again.' },
        }).then((url) => {
            setPending(null);
            window.location.href = url;
        }).catch(() => setPending(null));
    };

    if (!formats.length) return null;

    return (
        <>
            <DropdownMenu>
                {iconOnly ? (
                    <DropdownMenuTrigger render={
                        <IconButton icon={Download} label={pending ? 'Preparing ' + pending + '…' : 'Download'} round={iconRound} size={iconSize} />
                    } />
                ) : (
                    <DropdownMenuTrigger render={<Button variant="secondary" icon={Download} loading={pending != null} />}>
                        {pending ? 'Preparing ' + pending + '…' : 'Download'}
                    </DropdownMenuTrigger>
                )}
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
