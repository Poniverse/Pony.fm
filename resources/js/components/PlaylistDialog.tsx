import React from 'react';
import { Check } from 'lucide-react';
import { Dialog } from '@/design-system/feedback/Dialog';
import { Button } from '@/design-system/core/Button';
import { Input } from '@/design-system/core/Input';
import { Switch } from '@/design-system/core/Switch';
import { api } from '@/lib/api';
import { emitPlaylistsChanged } from '@/lib/events';
import type { PlaylistSummary } from '@/lib/types';

export interface PlaylistFormValues {
    title: string;
    description: string;
    is_public: boolean;
    is_pinned: boolean;
}

/** Create/edit playlist dialog — posts to the existing /api/web endpoints. */
export function PlaylistDialog({ open, onClose, onSaved, playlist }: {
    open: boolean;
    onClose: () => void;
    onSaved?: (playlist: PlaylistSummary) => void;
    /** Pass to edit an existing playlist */
    playlist?: (PlaylistFormValues & { id: number }) | null;
}) {
    const [form, setForm] = React.useState<PlaylistFormValues>({ title: '', description: '', is_public: true, is_pinned: true });
    const [errors, setErrors] = React.useState<Record<string, string[]>>({});
    const [busy, setBusy] = React.useState(false);

    React.useEffect(() => {
        if (open) {
            setForm(playlist
                ? { title: playlist.title, description: playlist.description, is_public: playlist.is_public, is_pinned: playlist.is_pinned }
                : { title: '', description: '', is_public: true, is_pinned: true });
            setErrors({});
        }
    }, [open, playlist]);

    const submit = () => {
        if (busy) return;
        setBusy(true);
        const url = playlist ? '/playlists/edit/' + playlist.id : '/playlists/create';
        api.post<PlaylistSummary>(url, form)
            .then(({ data }) => {
                emitPlaylistsChanged();
                onSaved?.(data);
                onClose();
            })
            .catch((e) => setErrors(e.response?.data?.errors ?? {}))
            .finally(() => setBusy(false));
    };

    return (
        <Dialog open={open} title={playlist ? 'Edit playlist' : 'New playlist'} onClose={onClose}
            footer={<>
                <Button variant="secondary" onClick={onClose}>Cancel</Button>
                <Button icon={Check} disabled={busy || !form.title.trim()} onClick={submit}>{playlist ? 'Save' : 'Create'}</Button>
            </>}>
            <div className="grid gap-3.5">
                <Input label="Title" value={form.title} error={errors.title?.[0]} onChange={(e) => setForm({ ...form, title: e.target.value })} />
                <Input label="Description" multiline rows={3} value={form.description} error={errors.description?.[0]} onChange={(e) => setForm({ ...form, description: e.target.value })} />
                <Switch label="Public — anyone can view and listen" checked={form.is_public} onChange={(v) => setForm({ ...form, is_public: v })} />
                <Switch label="Pin to my sidebar" checked={form.is_pinned} onChange={(v) => setForm({ ...form, is_pinned: v })} />
            </div>
        </Dialog>
    );
}
