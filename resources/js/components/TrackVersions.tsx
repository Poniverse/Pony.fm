import React from 'react';
import { Check, Upload } from 'lucide-react';
import { Button } from '@/design-system/core/Button';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';
import { api } from '@/lib/api';
import { timeAgo } from '@/lib/format';
import { cn } from '@/lib/utils';

interface TrackVersion {
    version: number;
    url: string;
    created_at: number;
}

/**
 * Track version management: upload a replacement master (re-encoding every
 * format) and switch between past versions. Same flow as the old editor's
 * version panel — POST version-upload, poll version-upload-status (202
 * processing / 201 done), GET version-change/{n} to activate one.
 */
export function TrackVersions({ trackId }: { trackId: number }) {
    const [versions, setVersions] = React.useState<TrackVersion[] | null>(null);
    const [currentVersion, setCurrentVersion] = React.useState<number | null>(null);
    const [uploading, setUploading] = React.useState<'sending' | 'processing' | null>(null);
    const [error, setError] = React.useState<string | null>(null);
    const [switching, setSwitching] = React.useState<number | null>(null);
    const inputRef = React.useRef<HTMLInputElement>(null);
    const timer = React.useRef<ReturnType<typeof setTimeout>>(undefined);

    const refresh = React.useCallback(() => {
        api.get<{ current_version: number; versions: TrackVersion[] }>('/tracks/' + trackId + '/versions')
            .then(({ data }) => {
                setVersions(data.versions);
                setCurrentVersion(data.current_version);
            })
            .catch(() => setVersions([]));
    }, [trackId]);

    React.useEffect(() => {
        refresh();
        return () => clearTimeout(timer.current);
    }, [refresh]);

    const pollStatus = () => {
        api.get('/tracks/' + trackId + '/version-upload-status', { validateStatus: (s) => s === 201 || s === 202 })
            .then(({ status }) => {
                if (status === 201) {
                    setUploading(null);
                    refresh();
                } else {
                    timer.current = setTimeout(pollStatus, 5000);
                }
            })
            .catch((e) => {
                setUploading(null);
                setError(e.response?.data?.error ?? 'Processing the new version failed.');
            });
    };

    const upload = (file: File) => {
        setError(null);
        setUploading('sending');
        const fd = new FormData();
        fd.append('track', file);
        api.post('/tracks/' + trackId + '/version-upload', fd)
            .then(() => {
                setUploading('processing');
                timer.current = setTimeout(pollStatus, 5000);
            })
            .catch((e) => {
                setUploading(null);
                const errors = e.response?.data?.errors as Record<string, string[]> | undefined;
                setError(errors ? Object.values(errors).flat().join(' ') : 'Upload failed.');
            });
    };

    const activate = (version: number) => {
        setSwitching(version);
        api.get('/tracks/' + trackId + '/version-change/' + version)
            .then(() => refresh())
            .catch(() => setError('Could not switch versions.'))
            .finally(() => setSwitching(null));
    };

    if (versions === null) return null;

    return (
        <section className="grid gap-2.5">
            <SectionHeader title="Versions" level={3} count={versions.length}
                action={
                    <Button size="sm" variant="secondary" disabled={uploading != null}
                        icon={Upload} loading={uploading != null}
                        onClick={() => inputRef.current?.click()}>
                        {uploading === 'sending' ? 'Uploading…' : uploading === 'processing' ? 'Processing…' : 'Upload new version'}
                    </Button>
                } />
            <input ref={inputRef} type="file" accept="audio/*,.flac,.wav,.aiff,.ogg,.mp3,.m4a" className="hidden"
                onChange={(e) => { const f = e.target.files?.[0]; if (f) upload(f); e.target.value = ''; }} />
            {error ? <span className="text-sm text-status-danger">{error}</span> : null}
            {versions.length === 0 ? (
                <p className="m-0 text-xs text-faint">
                    Replacing the audio re-encodes every download format; older masters stay switchable here.
                </p>
            ) : (
                <div className="grid gap-0.5">
                    {[...versions].sort((a, b) => b.version - a.version).map((v) => {
                        const isCurrent = v.version === currentVersion;
                        return (
                            <div key={v.version} className={cn('flex items-center gap-2.5 rounded-sm px-2.5 py-1.5', isCurrent ? 'bg-brand-quiet' : 'bg-transparent')}>
                                <span className={cn('font-mono text-xs', isCurrent ? 'text-brand-text' : 'text-heading')}>v{v.version}</span>
                                <span className="flex-1 text-2xs text-faint">{timeAgo(new Date(v.created_at * 1000))}</span>
                                {isCurrent ? (
                                    <span className="text-2xs uppercase tracking-caps text-brand-text">Current</span>
                                ) : (
                                    <Button size="sm" variant="ghost" disabled={switching != null}
                                        icon={Check} loading={switching === v.version}
                                        onClick={() => activate(v.version)}>
                                        Use this version
                                    </Button>
                                )}
                            </div>
                        );
                    })}
                </div>
            )}
        </section>
    );
}
