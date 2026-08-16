import React from 'react';
import { Head } from '@inertiajs/react';
import { Music } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';
import { CollectionCard } from '@/design-system/music/CollectionCard';
import { EmptyState } from '@/design-system/feedback/EmptyState';
import { ArtistHeader } from '@/components/ArtistHeader';
import { TrackList } from '@/components/TrackList';
import type { AlbumSummary, ArtistData, TrackSummary } from '@/lib/types';

interface ArtistContentProps {
    artist: ArtistData;
    singles: TrackSummary[];
    albumTracks: TrackSummary[];
    albums: AlbumSummary[];
}

export default function ArtistContentPage({ artist, singles, albumTracks, albums }: ArtistContentProps) {
    const empty = singles.length === 0 && albums.length === 0 && albumTracks.length === 0;
    return (
        <div>
            <Head title={artist.name + ' - Content'} />
            <ArtistHeader artist={artist} active="content" />
            <div className="grid max-w-(--content-max) gap-7 px-7 pt-6 pb-12">
                {empty ? <EmptyState icon={Music} title="Nothing published yet" /> : null}
                {albums.length ? (
                    <section>
                        <SectionHeader title="Albums" count={albums.length} />
                        <div className="grid grid-cols-[repeat(auto-fill,minmax(290px,1fr))] gap-2.5">
                            {albums.map((a) => (
                                <CollectionCard key={a.id} title={a.title} subtitle={'by ' + a.user.name} cover={a.covers.small}
                                    count={a.track_count} href={a.url} />
                            ))}
                        </div>
                    </section>
                ) : null}
                {singles.length ? (
                    <section>
                        <SectionHeader title="Singles" count={singles.length} />
                        <TrackList tracks={singles} />
                    </section>
                ) : null}
                {albumTracks.length ? (
                    <section>
                        <SectionHeader title="From albums" count={albumTracks.length} />
                        <TrackList tracks={albumTracks} />
                    </section>
                ) : null}
            </div>
        </div>
    );
}

ArtistContentPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
