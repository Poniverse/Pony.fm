import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Check, ExternalLink, Eye, Trash2 } from 'lucide-react';
import { Dialog } from '@/design-system/feedback/Dialog';
import { Button } from '@/design-system/core/Button';
import { TrackVersions } from '@/components/TrackVersions';
import { DatePicker } from '@/design-system/core/DatePicker';
import { Input } from '@/design-system/core/Input';
import { Switch } from '@/design-system/core/Switch';
import { Select } from '@/design-system/core/Select';
import { Loader } from '@/design-system/core/Loader';
import { api } from '@/lib/api';
import { useTaxonomies } from '@/hooks/useTaxonomies';
import { ImageUpload, type ImageUploadValue } from '@/components/ImageUpload';
import type { SharedProps } from '@/lib/types';
import { cn } from '@/lib/utils';

/** Private payload from GET /api/web/tracks/edit/{id}. */
interface TrackEditData {
    id: number;
    slug: string;
    title: string;
    description: string | null;
    lyrics: string | null;
    is_vocal: boolean;
    is_explicit: boolean;
    is_downloadable: boolean;
    is_listed: boolean;
    is_published: boolean;
    released_at: string | null;
    genre_id: number | null;
    track_type_id: number | null;
    license_id: number | null;
    album_id: number | null;
    show_songs: { id: number; title: string }[];
    cover_url: string | null;
    real_cover_url?: string | null;
    username?: string;
}

interface TrackForm {
    title: string;
    description: string;
    lyrics: string;
    is_vocal: boolean;
    is_explicit: boolean;
    is_downloadable: boolean;
    is_listed: boolean;
    released_at: string;
    genre_id: string;
    track_type_id: string;
    license_id: number | null;
    show_song_ids: number[];
    username: string;
}

const OFFICIAL_SONG_REMIX_TYPE_ID = 2;
const UNCLASSIFIED_TYPE_ID = 6;

/** Fields rendered inline; anything else the API complains about goes in the bottom list. */
const KNOWN_ERROR_FIELDS = ['title', 'description', 'lyrics', 'released_at', 'genre_id', 'track_type_id', 'license_id', 'username', 'show_song_ids'];

const labelClass = 'mb-1.5 block text-2xs font-bold uppercase tracking-caps text-muted-foreground';

/** The full track edit form, ported from the legacy Angular pfm-track-editor. */
export function TrackEditor({ trackId, onSaved, onDeleted }: {
    trackId: number;
    onSaved?: () => void;
    onDeleted?: () => void;
}) {
    const { auth } = usePage<SharedProps>().props;
    const isAdmin = !!auth.user?.is_admin;
    const taxonomies = useTaxonomies();

    const [track, setTrack] = React.useState<TrackEditData | null>(null);
    const [loadError, setLoadError] = React.useState<string | null>(null);
    const [form, setForm] = React.useState<TrackForm | null>(null);
    // undefined = untouched, null = explicitly cleared by the user
    const [cover, setCover] = React.useState<ImageUploadValue | undefined>(undefined);
    const [errors, setErrors] = React.useState<Record<string, string[]>>({});
    const [busy, setBusy] = React.useState(false);
    const [confirmingDelete, setConfirmingDelete] = React.useState(false);
    const [deleting, setDeleting] = React.useState(false);

    React.useEffect(() => {
        let mounted = true;
        setTrack(null);
        setForm(null);
        setCover(null);
        setErrors({});
        api.get<TrackEditData>('/tracks/edit/' + trackId)
            .then(({ data }) => {
                if (!mounted) return;
                setTrack(data);
                setForm({
                    title: data.title ?? '',
                    description: data.description ?? '',
                    lyrics: data.lyrics ?? '',
                    is_vocal: !!data.is_vocal,
                    is_explicit: !!data.is_explicit,
                    is_downloadable: !!data.is_downloadable,
                    is_listed: !!data.is_listed,
                    released_at: data.released_at ?? '',
                    genre_id: data.genre_id != null ? String(data.genre_id) : '',
                    // Fresh uploads sit in the excluded "unclassified" type (6);
                    // treat it as "not chosen yet" so the select can't lie.
                    track_type_id: data.track_type_id != null && data.track_type_id !== UNCLASSIFIED_TYPE_ID
                        ? String(data.track_type_id)
                        : '',
                    license_id: data.license_id,
                    show_song_ids: (data.show_songs ?? []).map((s) => s.id),
                    username: data.username ?? '',
                });
            })
            .catch(() => { if (mounted) setLoadError('Couldn’t load this track for editing.'); });
        return () => { mounted = false; };
    }, [trackId]);

    if (loadError) {
        return <div className="py-[18px] text-sm text-status-danger">{loadError}</div>;
    }
    if (!track || !form || !taxonomies) {
        return (
            <div className="flex items-center gap-2 py-[18px] text-sm text-muted-foreground">
                <Loader size={18} /> Loading track…
            </div>
        );
    }

    const isRemix = Number(form.track_type_id) === OFFICIAL_SONG_REMIX_TYPE_ID;
    const trackTypes = taxonomies.track_types.filter((t) => t.id !== UNCLASSIFIED_TYPE_ID);
    const unknownErrors = Object.entries(errors)
        .filter(([field]) => !KNOWN_ERROR_FIELDS.includes(field))
        .flatMap(([, messages]) => messages);

    const toggleShowSong = (id: number) => {
        setForm({
            ...form,
            show_song_ids: form.show_song_ids.includes(id)
                ? form.show_song_ids.filter((s) => s !== id)
                : [...form.show_song_ids, id],
        });
    };

    const save = () => {
        if (busy) return;
        setBusy(true);
        const fd = new FormData();
        fd.append('title', form.title);
        fd.append('description', form.description);
        fd.append('lyrics', form.lyrics);
        fd.append('is_vocal', form.is_vocal ? 'true' : 'false');
        fd.append('is_explicit', form.is_explicit ? 'true' : 'false');
        fd.append('is_downloadable', form.is_downloadable ? 'true' : 'false');
        fd.append('is_listed', form.is_listed ? 'true' : 'false');
        fd.append('released_at', form.released_at || '');
        fd.append('genre_id', form.genre_id);
        fd.append('track_type_id', form.track_type_id);
        fd.append('license_id', form.license_id != null ? String(form.license_id) : '');
        fd.append('album_id', track.album_id != null ? String(track.album_id) : '');
        if (isRemix) fd.append('show_song_ids', JSON.stringify(form.show_song_ids));
        if (isAdmin) fd.append('username', form.username);
        if (cover?.type === 'file') fd.append('cover', cover.file);
        else if (cover?.type === 'gallery') fd.append('cover_id', String(cover.imageId));
        else if (cover === null && track.cover_url) fd.append('remove_cover', 'true');
        api.post('/tracks/edit/' + trackId, fd)
            .then(() => {
                setErrors({});
                onSaved?.();
            })
            .catch((e) => setErrors(e.response?.data?.errors ?? {}))
            .finally(() => setBusy(false));
    };

    const destroy = () => {
        if (deleting) return;
        setDeleting(true);
        api.post('/tracks/delete/' + trackId)
            .then(() => {
                setConfirmingDelete(false);
                onDeleted?.();
            })
            .catch((e) => setErrors(e.response?.data?.errors ?? {}))
            .finally(() => setDeleting(false));
    };

    return (
        <div className="grid gap-[18px]">
            <div className="flex items-center justify-between gap-3">
                <span className="min-w-0 truncate font-display text-lg font-semibold text-heading">{track.title}</span>
                <Button
                    render={<Link href={'/tracks/' + track.id + '-' + track.slug} />}
                    variant="ghost"
                    size="sm"
                    icon={track.is_published ? ExternalLink : Eye}
                >
                    {track.is_published ? 'View public page' : 'Preview'}
                </Button>
            </div>

            <Input label="Title" value={form.title} error={errors.title?.[0]} onChange={(e) => setForm({ ...form, title: e.target.value })} />

            <div className="grid gap-3">
                <Switch label="This track has vocals" checked={form.is_vocal} onChange={(v) => setForm({ ...form, is_vocal: v })} />
                {form.is_vocal ? (
                    <Input label="Lyrics" multiline rows={6} value={form.lyrics} error={errors.lyrics?.[0]} onChange={(e) => setForm({ ...form, lyrics: e.target.value })} />
                ) : null}
                <Switch label="Explicit" checked={form.is_explicit} onChange={(v) => setForm({ ...form, is_explicit: v })} />
                <Switch label="Downloadable" checked={form.is_downloadable} onChange={(v) => setForm({ ...form, is_downloadable: v })} />
                <Switch label="Listed publicly" checked={form.is_listed} onChange={(v) => setForm({ ...form, is_listed: v })} />
            </div>

            <div className="grid grid-cols-2 gap-3.5">
                <div>
                    <Select label="Genre" value={form.genre_id}
                        options={[
                            ...(form.genre_id === '' ? [{ value: '', label: 'Choose a genre…' }] : []),
                            ...taxonomies.genres.map((g) => ({ value: String(g.id), label: g.name })),
                        ]}
                        onChange={(e) => setForm({ ...form, genre_id: e.target.value })} />
                    {errors.genre_id?.[0] ? <span className="mt-[5px] block text-2xs text-status-danger">{errors.genre_id[0]}</span> : null}
                </div>
                <div>
                    <Select label="Track type" value={form.track_type_id}
                        options={[
                            ...(form.track_type_id === '' ? [{ value: '', label: 'Choose a type…' }] : []),
                            ...trackTypes.map((t) => ({ value: String(t.id), label: t.title })),
                        ]}
                        onChange={(e) => setForm({ ...form, track_type_id: e.target.value })} />
                    {errors.track_type_id?.[0] ? <span className="mt-[5px] block text-2xs text-status-danger">{errors.track_type_id[0]}</span> : null}
                </div>
            </div>

            {isRemix ? (
                <div>
                    <span className={labelClass}>Songs remixed</span>
                    <div className="box-border grid max-h-[220px] gap-0.5 overflow-y-auto rounded-control border border-border bg-surface-3 p-2">
                        {taxonomies.show_songs.map((song) => (
                            <label key={song.id} className="flex cursor-pointer items-center gap-2 rounded-xs px-1.5 py-1 text-sm text-foreground">
                                <input type="checkbox" checked={form.show_song_ids.includes(song.id)} onChange={() => toggleShowSong(song.id)} />
                                {song.title}
                            </label>
                        ))}
                    </div>
                    {errors.show_song_ids?.[0] ? <span className="mt-[5px] block text-2xs text-status-danger">{errors.show_song_ids[0]}</span> : null}
                </div>
            ) : null}

            <DatePicker label="Released" value={form.released_at} error={errors.released_at?.[0]} onChange={(v) => setForm({ ...form, released_at: v })} />

            <Input label="Description" multiline rows={5} value={form.description} error={errors.description?.[0]} onChange={(e) => setForm({ ...form, description: e.target.value })} />

            <ImageUpload label="Cover art" currentUrl={track.cover_url} onChange={setCover} />

            <div>
                <span className={labelClass}>License</span>
                <div className="grid grid-cols-2 gap-2.5">
                    {taxonomies.licenses.map((license) => {
                        const selected = form.license_id === license.id;
                        return (
                            <button key={license.id} type="button" onClick={() => setForm({ ...form, license_id: license.id })}
                                className={cn(
                                    'grid cursor-pointer gap-[5px] rounded-control border border-solid px-[13px] py-[11px] text-left font-text [transition:var(--transition-hover)]',
                                    selected ? 'border-purple-600 bg-brand-quiet' : 'border-border bg-surface-3',
                                )}>
                                <span className={cn('text-sm font-semibold', selected ? 'text-brand-text' : 'text-heading')}>
                                    {license.title}
                                </span>
                                <span className="text-2xs leading-normal text-muted-foreground">
                                    {license.description.length > 120 ? license.description.slice(0, 120).trimEnd() + '…' : license.description}
                                </span>
                            </button>
                        );
                    })}
                </div>
                {errors.license_id?.[0] ? <span className="mt-[5px] block text-2xs text-status-danger">{errors.license_id[0]}</span> : null}
            </div>

            {isAdmin ? (
                <Input label="Owner username" value={form.username} error={errors.username?.[0]} hint="Admin only — reassigns this track to another user." onChange={(e) => setForm({ ...form, username: e.target.value })} />
            ) : null}

            <TrackVersions trackId={trackId} />

            {unknownErrors.length ? (
                <ul className="m-0 pl-[18px] text-sm text-status-danger">
                    {unknownErrors.map((message, i) => <li key={i}>{message}</li>)}
                </ul>
            ) : null}

            <div className="flex items-center gap-2">
                <Button icon={Check} loading={busy} disabled={busy || !form.title.trim()} onClick={save}>
                    {track.is_published ? 'Save changes' : 'Publish'}
                </Button>
                <span className="ml-auto">
                    <Button variant="ghost" icon={Trash2} onClick={() => setConfirmingDelete(true)}>Delete track</Button>
                </span>
            </div>

            <Dialog open={confirmingDelete} title="Delete this track?" onClose={() => setConfirmingDelete(false)}
                footer={<>
                    <Button variant="secondary" onClick={() => setConfirmingDelete(false)}>Cancel</Button>
                    <Button variant="danger" icon={Trash2} loading={deleting} disabled={deleting} onClick={destroy}>Delete</Button>
                </>}>
                “{track.title}” and its stats will be permanently deleted. This can’t be undone.
            </Dialog>
        </div>
    );
}
