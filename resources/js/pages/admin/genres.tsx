import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { AdminHeader } from '@/components/AdminHeader';
import { TaxonomyManager, type TaxonomyItem } from '@/components/TaxonomyManager';

interface RawGenre {
    id: number;
    name: string;
    track_count?: number;
    track_count_relation?: { aggregate?: number } | null;
}

export default function AdminGenresPage({ genres }: { genres: RawGenre[] }) {
    const items: TaxonomyItem[] = genres.map((g) => ({
        id: g.id,
        name: g.name,
        track_count: g.track_count ?? g.track_count_relation?.aggregate ?? 0,
    }));
    return (
        <div className="grid max-w-[720px] gap-5 px-7 pt-6 pb-12">
            <Head title="Admin - Genres" />
            <AdminHeader active="genres" />
            <TaxonomyManager items={items} endpoint="/admin/genres" nameField="name"
                destinationField="destination_genre_id" noun="genre" />
        </div>
    );
}

AdminGenresPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
