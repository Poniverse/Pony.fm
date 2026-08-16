import React from 'react';
import { usePage } from '@inertiajs/react';
import { Image as ImageIcon, Music, Upload, X } from 'lucide-react';
import { Dialog } from '@/design-system/feedback/Dialog';
import { Button } from '@/design-system/core/Button';
import { EmptyState } from '@/design-system/feedback/EmptyState';
import { Loader } from '@/design-system/core/Loader';
import { api } from '@/lib/api';
import type { SharedProps } from '@/lib/types';
import { cn } from '@/lib/utils';

export type ImageUploadValue = { type: 'file'; file: File } | { type: 'gallery'; imageId: number } | null;

interface GalleryImage {
    id: number;
    urls: { small?: string; normal?: string };
}

const ACCEPTED_TYPES = ['image/png', 'image/jpeg'];

/** Cover picker: upload a PNG/JPEG or reuse a previously uploaded gallery image. */
export function ImageUpload({ label, currentUrl, onChange }: {
    label?: string;
    currentUrl?: string | null;
    onChange: (v: ImageUploadValue) => void;
}) {
    const { auth } = usePage<SharedProps>().props;
    const fileRef = React.useRef<HTMLInputElement>(null);
    const [preview, setPreview] = React.useState<string | null>(null);
    const [error, setError] = React.useState<string | null>(null);
    const [galleryOpen, setGalleryOpen] = React.useState(false);
    const [gallery, setGallery] = React.useState<GalleryImage[] | null>(null);
    const [galleryError, setGalleryError] = React.useState<string | null>(null);

    const shown = preview ?? currentUrl ?? null;

    const pickFile = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        e.target.value = '';
        if (!file) return;
        if (!ACCEPTED_TYPES.includes(file.type)) {
            setError('Cover art must be a PNG or JPEG image.');
            return;
        }
        setError(null);
        const reader = new FileReader();
        reader.onload = () => setPreview(typeof reader.result === 'string' ? reader.result : null);
        reader.readAsDataURL(file);
        onChange({ type: 'file', file });
    };

    const openGallery = () => {
        setGalleryOpen(true);
        if (gallery || !auth.user) return;
        api.get<GalleryImage[]>('/users/' + auth.user.id + '/images')
            .then(({ data }) => setGallery(data))
            .catch(() => setGalleryError('Couldn’t load your images. Please try again.'));
    };

    const pickGalleryImage = (image: GalleryImage) => {
        setError(null);
        setPreview(image.urls?.normal ?? image.urls?.small ?? null);
        onChange({ type: 'gallery', imageId: image.id });
        setGalleryOpen(false);
    };

    const clear = () => {
        setError(null);
        setPreview(null);
        onChange(null);
    };

    return (
        <div>
            {label ? <span className={cn('mb-1.5 block text-2xs font-bold uppercase tracking-caps', error ? 'text-status-danger' : 'text-muted-foreground')}>{label}</span> : null}
            <div className="flex items-start gap-3.5">
                {shown ? (
                    <img src={shown} alt={label ? label + ' preview' : 'Cover preview'}
                        className="block h-[120px] w-[120px] rounded-art object-cover shadow-(--ring-inset)" />
                ) : (
                    <div className="box-border grid h-[120px] w-[120px] place-items-center rounded-art border border-dashed border-purple-600 bg-brand-quiet">
                        <Music className="size-6 text-brand-text" aria-hidden="true" />
                    </div>
                )}
                <div className="grid justify-items-start gap-2">
                    <input ref={fileRef} type="file" accept="image/png,image/jpeg" onChange={pickFile} className="hidden" />
                    <Button variant="secondary" icon={Upload} onClick={() => fileRef.current?.click()}>Choose file</Button>
                    <Button variant="secondary" icon={ImageIcon} onClick={openGallery}>Gallery</Button>
                    <Button variant="ghost" icon={X} onClick={clear}>Clear</Button>
                </div>
            </div>
            {error ? <span className="mt-[5px] block text-2xs text-status-danger">{error}</span> : null}

            <Dialog open={galleryOpen} title="Your images" subtitle="Pick a previously uploaded image" onClose={() => setGalleryOpen(false)} width={520}>
                {galleryError ? (
                    <div className="text-sm text-status-danger">{galleryError}</div>
                ) : gallery == null ? (
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Loader size={18} /> Loading images…
                    </div>
                ) : gallery.length === 0 ? (
                    <EmptyState icon={ImageIcon} title="No images yet">
                        Images you upload as cover art will show up here for reuse.
                    </EmptyState>
                ) : (
                    <div className="grid max-h-[340px] grid-cols-[repeat(auto-fill,minmax(88px,1fr))] gap-2.5 overflow-y-auto">
                        {gallery.map((image) => (
                            <button key={image.id} type="button" onClick={() => pickGalleryImage(image)}
                                className="cursor-pointer overflow-hidden rounded-control border border-solid border-border bg-transparent p-0 leading-[0] hover:border-(--border-focus)">
                                <img src={image.urls?.small ?? image.urls?.normal ?? ''} alt={'Image #' + image.id}
                                    className="block aspect-square w-full object-cover" />
                            </button>
                        ))}
                    </div>
                )}
            </Dialog>
        </div>
    );
}
