import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { FolderOpen, Upload } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { AccountHeader } from '@/components/AccountHeader';
import { Button } from '@/design-system/core/Button';
import { cn } from '@/lib/utils';
import { useUploadQueue, type UploadItem } from '@/hooks/useUploadQueue';

function Row({ item, accountSlug }: { item: UploadItem; accountSlug: string }) {
    return (
        <div className="grid gap-1.5 rounded-card border border-border-subtle bg-surface-2 px-3.5 py-3">
            <div className="flex items-baseline gap-2.5">
                <span className="min-w-0 flex-1 truncate text-sm font-semibold text-heading">{item.name}</span>
                <span
                    className={cn(
                        'text-2xs uppercase tracking-caps',
                        item.status === 'error' ? 'text-status-danger' : item.status === 'done' ? 'text-status-success' : 'text-muted-foreground',
                    )}>
                    {item.status === 'uploading' ? item.progress + '%'
                        : item.status === 'processing' ? 'Processing…'
                        : item.status === 'done' ? 'Ready'
                        : 'Failed'}
                </span>
            </div>
            {item.status === 'uploading' || item.status === 'processing' ? (
                <div className="h-[5px] overflow-hidden rounded-pill bg-(--player-transport-track)">
                    <div
                        className={cn(
                            'h-full rounded-pill transition-[width] duration-(--dur-fast) ease-(--ease-standard)',
                            item.status === 'processing' ? 'bg-status-warning' : 'bg-purple-400',
                        )}
                        style={{ width: (item.status === 'processing' ? 100 : item.progress) + '%' }} />
                </div>
            ) : null}
            {item.status === 'error' ? (
                <span className="text-xs text-status-danger">{item.error}</span>
            ) : null}
            {item.status === 'done' && item.trackId ? (
                <span className="text-xs">
                    <Link href={'/' + accountSlug + '/account/tracks/edit/' + item.trackId}>Add details &amp; publish →</Link>
                </span>
            ) : null}
        </div>
    );
}

export default function UploaderPage({ accountSlug }: { accountSlug: string }) {
    const queue = useUploadQueue(accountSlug);
    const [dragOver, setDragOver] = React.useState(false);
    const inputRef = React.useRef<HTMLInputElement>(null);

    const addFiles = (files: FileList | null) => {
        if (!files) return;
        Array.from(files).forEach(queue.upload);
    };

    return (
        <div className="grid max-w-[860px] gap-5 px-7 pt-6 pb-12">
            <Head title="Upload music" />
            <AccountHeader slug={accountSlug} active="uploader" />

            <div
                onDragOver={(e) => { e.preventDefault(); setDragOver(true); }}
                onDragLeave={() => setDragOver(false)}
                onDrop={(e) => { e.preventDefault(); setDragOver(false); addFiles(e.dataTransfer.files); }}
                className={cn(
                    'grid justify-items-center gap-2.5 rounded-card border-2 border-dashed px-5 py-[46px] text-center transition-[background,color,border-color] duration-(--dur-fast) ease-(--ease-standard)',
                    dragOver ? 'border-purple-400 bg-surface-active' : 'border-purple-600 bg-brand-quiet',
                )}>
                <Upload className="size-7 text-brand-text" aria-hidden="true" />
                <div className="font-display text-xl font-semibold leading-(--leading-normal) text-heading">
                    Drop your tracks here
                </div>
                <div className="max-w-[460px] text-sm text-muted-foreground">
                    Lossless files (FLAC, WAV, AIFF) are preferred — Pony.fm transcodes every format your listeners could want. You can add details and publish once processing finishes.
                </div>
                <Button variant="secondary" icon={FolderOpen} onClick={() => inputRef.current?.click()}>Or pick files…</Button>
                <input ref={inputRef} type="file" multiple accept="audio/*,.flac,.alac,.wav,.aiff,.ogg,.mp3,.m4a"
                    className="hidden" onChange={(e) => { addFiles(e.target.files); e.target.value = ''; }} />
            </div>

            {queue.items.length ? (
                <div className="grid gap-2">
                    {queue.items.map((item) => <Row key={item.key} item={item} accountSlug={accountSlug} />)}
                </div>
            ) : null}
        </div>
    );
}

UploaderPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
