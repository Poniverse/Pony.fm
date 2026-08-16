import React from 'react';
import AppLayout from '@/layouts/AppLayout';
import { CollectionShow } from '@/components/CollectionShow';
import type { AlbumShow } from '@/lib/types';

export default function AlbumShowPage({ album }: { album: AlbumShow }) {
    return <CollectionShow kind="album" data={album} />;
}

AlbumShowPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
