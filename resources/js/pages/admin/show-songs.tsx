import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { AdminHeader } from '@/components/AdminHeader';
import { TaxonomyManager, type TaxonomyItem } from '@/components/TaxonomyManager';

interface RawSong {
    id: number;
    title: string;
    track_count?: number;
    track_count_relation?: { aggregate?: number } | null;
}

export default function AdminShowSongsPage({ songs }: { songs: RawSong[] }) {
    const items: TaxonomyItem[] = songs.map((s) => ({
        id: s.id,
        name: s.title,
        track_count: s.track_count ?? s.track_count_relation?.aggregate ?? 0,
    }));
    return (
        <div className="grid max-w-[720px] gap-5 px-7 pt-6 pb-12">
            <Head title="Admin - Show songs" />
            <AdminHeader active="show-songs" />
            <TaxonomyManager items={items} endpoint="/admin/showsongs" nameField="title"
                destinationField="destination_song_id" noun="show song" />
        </div>
    );
}

AdminShowSongsPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
