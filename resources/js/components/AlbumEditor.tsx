import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { DragDropProvider } from '@dnd-kit/react';
import { useSortable } from '@dnd-kit/react/sortable';
import { move } from '@dnd-kit/helpers';
import { Check, ExternalLink, GripVertical, Music, Plus, Trash2, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Dialog } from '@/design-system/feedback/Dialog';
import { Button } from '@/design-system/core/Button';
import { IconButton } from '@/design-system/core/IconButton';
import { Input } from '@/design-system/core/Input';
import { Loader } from '@/design-system/core/Loader';
import { EmptyState } from '@/design-system/feedback/EmptyState';
import { api } from '@/lib/api';
import { ImageUpload, type ImageUploadValue } from '@/components/ImageUpload';
import type { SharedProps } from '@/lib/types';

/** Private payload from GET /api/web/albums/edit/{id}. */
interface AlbumEditData {
    id: number;
    title?: string;
    slug?: string;
    description?: string | null;
    cover_url?: string | null;
    track_ids?: number[];
    tracks?: { id: number; title: string }[];
}

/** Private track summary from GET /api/web/users/{id}/tracks. */
interface OwnTrack {
    id: number;
    title: string;
    is_published?: boolean;
    cover_url?: string | null;
}

/** Fields rendered inline; anything else the API complains about goes in the bottom list. */
const KNOWN_ERROR_FIELDS = ['title', 'description', 'track_ids'];

const labelClass = 'mb-1.5 block text-2xs font-bold uppercase tracking-caps text-muted-foreground';

const rowClass = 'flex items-center gap-2 border-b border-border-subtle px-2 py-1.5 text-sm text-foreground';

function AlbumTrackRow({ id, index, children }: { id: number; index: number; children: React.ReactNode }) {
    const { ref, isDragging } = useSortable({ id, index });
    return (
        <div ref={ref} className={cn(rowClass, 'cursor-grab bg-surface-3 active:cursor-grabbing', isDragging && 'z-10 opacity-90 shadow-pop')}>
            {children}
        </div>
    );
}

/** Create/edit album form with an ordered track picker. */
export function AlbumEditor({ albumId, onSaved, onDeleted }: {
    albumId?: number | null;
    onSaved?: () => void;
    onDeleted?: () => void;
}) {
    const { auth } = usePage<SharedProps>().props;
    const userId = auth.user?.id;

    const [title, setTitle] = React.useState('');
    const [slug, setSlug] = React.useState<string | null>(null);
    const [description, setDescription] = React.useState('');
    const [coverUrl, setCoverUrl] = React.useState<string | null>(null);
    const [trackIds, setTrackIds] = React.useState<number[]>([]);
    /** Titles for tracks in the album payload that aren't in the user's own list. */
    const [albumTrackTitles, setAlbumTrackTitles] = React.useState<Record<number, string>>({});
    const [ownTracks, setOwnTracks] = React.useState<OwnTrack[] | null>(null);
    /** undefined = untouched, null = cleared. */
    const [cover, setCover] = React.useState<ImageUploadValue | undefined>(undefined);
    const [loading, setLoading] = React.useState(!!albumId);
    const [loadError, setLoadError] = React.useState<string | null>(null);
    const [errors, setErrors] = React.useState<Record<string, string[]>>({});
    const [busy, setBusy] = React.useState(false);
    const [confirmingDelete, setConfirmingDelete] = React.useState(false);
    const [deleting, setDeleting] = React.useState(false);

    React.useEffect(() => {
        let mounted = true;
        if (albumId) {
            setLoading(true);
            api.get<AlbumEditData>('/albums/edit/' + albumId)
                .then(({ data }) => {
                    if (!mounted) return;
                    setTitle(data.title ?? '');
                    setSlug(data.slug ?? null);
                    setDescription(data.description ?? '');
                    setCoverUrl(data.cover_url ?? null);
                    const tracks = Array.isArray(data.tracks) ? data.tracks : [];
                    const ids = Array.isArray(data.track_ids) ? data.track_ids : tracks.map((t) => t.id);
                    setTrackIds(ids.filter((id) => typeof id === 'number'));
                    setAlbumTrackTitles(Object.fromEntries(tracks.map((t) => [t.id, t.title])));
                })
                .catch(() => { if (mounted) setLoadError('Couldn’t load this album for editing.'); })
                .finally(() => { if (mounted) setLoading(false); });
        }
        return () => { mounted = false; };
    }, [albumId]);

    React.useEffect(() => {
        if (userId == null) return;
        let mounted = true;
        api.get<OwnTrack[]>('/users/' + userId + '/tracks')
            .then(({ data }) => {
                if (!mounted) return;
                setOwnTracks(data);
            })
            .catch(() => { if (mounted) setOwnTracks([]); });
        return () => { mounted = false; };
    }, [userId]);

    if (loadError) {
        return <div className="py-[18px] text-sm text-status-danger">{loadError}</div>;
    }
    if (loading || ownTracks == null) {
        return (
            <div className="flex items-center gap-2 py-[18px] text-sm text-muted-foreground">
                <Loader size={18} /> Loading album…
            </div>
        );
    }

    const titleFor = (id: number) => ownTracks.find((t) => t.id === id)?.title ?? albumTrackTitles[id] ?? 'Track #' + id;
    const available = ownTracks.filter((t) => !trackIds.includes(t.id));
    const unknownErrors = Object.entries(errors)
        .filter(([field]) => !KNOWN_ERROR_FIELDS.includes(field))
        .flatMap(([, messages]) => messages);

    const save = () => {
        if (busy) return;
        setBusy(true);
        const fd = new FormData();
        fd.append('title', title);
        fd.append('description', description);
        fd.append('track_ids', trackIds.join(','));
        if (cover?.type === 'file') fd.append('cover', cover.file);
        else if (cover?.type === 'gallery') fd.append('cover_id', String(cover.imageId));
        else if (cover === null && coverUrl) fd.append('remove_cover', 'true');
        if (!albumId && userId != null) fd.append('user_id', String(userId));
        api.post(albumId ? '/albums/edit/' + albumId : '/albums/create', fd)
            .then(() => {
                setErrors({});
                onSaved?.();
            })
            .catch((e) => setErrors(e.response?.data?.errors ?? {}))
            .finally(() => setBusy(false));
    };

    const destroy = () => {
        if (deleting || !albumId) return;
        setDeleting(true);
        api.post('/albums/delete/' + albumId)
            .then(() => {
                setConfirmingDelete(false);
                onDeleted?.();
            })
            .catch((e) => setErrors(e.response?.data?.errors ?? {}))
            .finally(() => setDeleting(false));
    };

    return (
        <div className="grid gap-[18px]">
            {albumId && slug ? (
                <div className="flex items-center justify-between gap-3">
                    <span className="min-w-0 truncate font-display text-lg font-semibold text-heading">{title}</span>
                    <Button render={<Link href={'/albums/' + albumId + '-' + slug} />} variant="ghost" size="sm" icon={ExternalLink}>
                        View public page
                    </Button>
                </div>
            ) : null}
            <Input label="Title" value={title} error={errors.title?.[0]} onChange={(e) => setTitle(e.target.value)} />
            <Input label="Description" multiline rows={5} value={description} error={errors.description?.[0]} onChange={(e) => setDescription(e.target.value)} />
            <ImageUpload label="Cover art" currentUrl={coverUrl} onChange={setCover} />

            <div className="grid grid-cols-2 items-start gap-3.5">
                <div>
                    <span className={labelClass}>In this album</span>
                    <div className="max-h-[320px] overflow-y-auto rounded-control border border-border bg-surface-3">
                        {trackIds.length === 0 ? (
                            <div className="px-3 py-3.5 text-sm text-faint">
                                No tracks yet — add some from the right.
                            </div>
                        ) : (
                            <DragDropProvider onDragEnd={(event) => {
                                setTrackIds((ids) => move(ids.map((id) => ({ id })), event).map((x) => x.id));
                            }}>
                                {trackIds.map((id, index) => (
                                    <AlbumTrackRow key={id} id={id} index={index}>
                                        <GripVertical className="size-3.5 flex-none text-faint" aria-hidden="true" />
                                        <span className="w-[18px] flex-none text-right font-mono text-2xs text-faint">{index + 1}</span>
                                        <span className="min-w-0 flex-1 truncate text-heading">{titleFor(id)}</span>
                                        <IconButton icon={X} label="Remove from album" size="sm" onClick={() => setTrackIds(trackIds.filter((t) => t !== id))} />
                                    </AlbumTrackRow>
                                ))}
                            </DragDropProvider>
                        )}
                    </div>
                    {errors.track_ids?.[0] ? <span className="mt-[5px] block text-2xs text-status-danger">{errors.track_ids[0]}</span> : null}
                </div>
                <div>
                    <span className={labelClass}>Your tracks</span>
                    {ownTracks.length === 0 ? (
                        <EmptyState icon={Music} title="No tracks">You haven’t uploaded any tracks to add yet.</EmptyState>
                    ) : (
                        <div className="max-h-[320px] overflow-y-auto rounded-control border border-border bg-surface-3">
                            {available.length === 0 ? (
                                <div className="px-3 py-3.5 text-sm text-faint">
                                    All of your tracks are in this album.
                                </div>
                            ) : available.map((track) => (
                                <div key={track.id} className={rowClass}>
                                    <span className="min-w-0 flex-1 truncate text-heading">{track.title}</span>
                                    {track.is_published === false ? <span className="flex-none text-2xs text-faint">unpublished</span> : null}
                                    <IconButton icon={Plus} label="Add to album" size="sm" onClick={() => setTrackIds([...trackIds, track.id])} />
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {unknownErrors.length ? (
                <ul className="m-0 pl-[18px] text-sm text-status-danger">
                    {unknownErrors.map((message, i) => <li key={i}>{message}</li>)}
                </ul>
            ) : null}

            <div className="flex items-center gap-2">
                <Button icon={Check} loading={busy} disabled={busy || !title.trim()} onClick={save}>
                    {albumId ? 'Save changes' : 'Create album'}
                </Button>
                {albumId ? (
                    <span className="ml-auto">
                        <Button variant="ghost" icon={Trash2} onClick={() => setConfirmingDelete(true)}>Delete album</Button>
                    </span>
                ) : null}
            </div>

            <Dialog open={confirmingDelete} title="Delete this album?" onClose={() => setConfirmingDelete(false)}
                footer={<>
                    <Button variant="secondary" onClick={() => setConfirmingDelete(false)}>Cancel</Button>
                    <Button variant="danger" icon={Trash2} loading={deleting} disabled={deleting} onClick={destroy}>Delete</Button>
                </>}>
                “{title || 'This album'}” will be permanently deleted. Its tracks stay on your profile.
            </Dialog>
        </div>
    );
}
