import { useRef, useState } from 'react';
import { api } from '@/lib/api';

export interface UploadItem {
    key: number;
    name: string;
    size: number;
    /** 0–100 while uploading */
    progress: number;
    status: 'uploading' | 'processing' | 'done' | 'error';
    error?: string;
    trackId?: number;
}

/**
 * The uploader queue: plain multipart POST with progress, then poll
 * upload-status every 5s (202 = still processing, 201 = done) — the same
 * flow as the old upload service.
 */
export function useUploadQueue(userSlug: string) {
    const [items, setItems] = useState<UploadItem[]>([]);
    const nextKey = useRef(1);

    const patch = (key: number, changes: Partial<UploadItem>) =>
        setItems((list) => list.map((i) => (i.key === key ? { ...i, ...changes } : i)));

    const pollStatus = (key: number, trackId: number) => {
        api.get('/tracks/' + trackId + '/upload-status', {
            validateStatus: (s) => s === 201 || s === 202,
        })
            .then(({ status }) => {
                if (status === 201) {
                    patch(key, { status: 'done' });
                } else {
                    setTimeout(() => pollStatus(key, trackId), 5000);
                }
            })
            .catch((e) => {
                // A dropped poll request says nothing about the track; keep polling.
                if (!e.response) {
                    setTimeout(() => pollStatus(key, trackId), 5000);
                    return;
                }
                patch(key, { status: 'error', error: e.response.data?.error ?? 'Processing failed.' });
            });
    };

    const upload = (file: File) => {
        const key = nextKey.current++;
        setItems((list) => [...list, { key, name: file.name, size: file.size, progress: 0, status: 'uploading' }]);

        const form = new FormData();
        form.append('track', file);
        form.append('user_slug', userSlug);

        let fullySent = false;

        api.post<{ id?: number; track_id?: number }>('/tracks/upload', form, {
            onUploadProgress: (e) => {
                if (!e.total) return;
                if (e.loaded >= e.total) fullySent = true;
                patch(key, { progress: Math.round((e.loaded / e.total) * 100) });
            },
        })
            .then(({ data }) => {
                const trackId = data.id ?? data.track_id;
                if (!trackId) {
                    patch(key, { status: 'error', error: 'Unexpected server response.' });
                    return;
                }
                patch(key, { status: 'processing', progress: 100, trackId });
                setTimeout(() => pollStatus(key, trackId), 5000);
            })
            .catch((e) => {
                // No response means the connection dropped. Only when the whole
                // file went out could the server have processed the upload
                // anyway; a partial or failed send can't have created a track.
                if (!e.response) {
                    patch(key, {
                        status: 'error',
                        error: fullySent
                            ? 'Connection interrupted. The upload may still have completed. Check your tracks before uploading this file again.'
                            : 'Connection failed. Check your connection and try again.',
                    });
                    return;
                }
                const errors = e.response.data?.errors as Record<string, string[]> | undefined;
                const message = errors ? Object.values(errors).flat().join(' ') : (e.response.data?.message ?? 'Upload failed.');
                patch(key, { status: 'error', error: message });
            });
    };

    return { items, upload };
}
