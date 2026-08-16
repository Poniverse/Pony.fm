import React from 'react';
import { Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { IconButton } from '@/design-system/core/IconButton';
import { CollectionShow } from '@/components/CollectionShow';
import type { AlbumShow } from '@/lib/types';

export default function AlbumShowPage({ album }: { album: AlbumShow }) {
    return (
        <CollectionShow
            kind="album"
            data={album}
            extraActions={album.permissions?.edit && album.user.slug ? (
                <IconButton icon={Pencil} label="Edit"
                    render={<Link href={'/' + album.user.slug + '/account/albums/edit/' + album.id} />} />
            ) : null}
        />
    );
}

AlbumShowPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
