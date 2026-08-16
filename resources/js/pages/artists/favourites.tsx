import React from 'react';
import { Head } from '@inertiajs/react';
import { Star } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';
import { CollectionCard } from '@/design-system/music/CollectionCard';
import { EmptyState } from '@/design-system/feedback/EmptyState';
import { ArtistHeader } from '@/components/ArtistHeader';
import { TrackList } from '@/components/TrackList';
import type { AlbumSummary, ArtistData, PlaylistSummary, TrackSummary } from '@/lib/types';

interface ArtistFavouritesProps {
    artist: ArtistData;
    tracks: TrackSummary[];
    albums: AlbumSummary[];
    playlists: PlaylistSummary[];
}

export default function ArtistFavouritesPage({ artist, tracks, albums, playlists }: ArtistFavouritesProps) {
    return (
        <div>
            <Head title={artist.name + ' - Favourites'} />
            <ArtistHeader artist={artist} active="favourites" />
            <div className="grid max-w-(--content-max) gap-7 px-7 pt-6 pb-12">
                {tracks.length === 0 && albums.length === 0 && playlists.length === 0 ? (
                    <EmptyState icon={Star} title="No favourites yet">
                        {artist.name} hasn't favourited anything… yet.
                    </EmptyState>
                ) : null}
                {tracks.length ? (
                    <section>
                        <SectionHeader title="Favourite tracks" count={tracks.length} />
                        <TrackList tracks={tracks} />
                    </section>
                ) : null}
                {albums.length ? (
                    <section>
                        <SectionHeader title="Favourite albums" count={albums.length} />
                        <div className="grid grid-cols-[repeat(auto-fill,minmax(290px,1fr))] gap-2.5">
                            {albums.map((a) => (
                                <CollectionCard key={a.id} title={a.title} subtitle={'by ' + a.user.name} cover={a.covers.small}
                                    count={a.track_count} href={a.url} />
                            ))}
                        </div>
                    </section>
                ) : null}
                {playlists.length ? (
                    <section>
                        <SectionHeader title="Favourite playlists" count={playlists.length} />
                        <div className="grid grid-cols-[repeat(auto-fill,minmax(290px,1fr))] gap-2.5">
                            {playlists.map((p) => (
                                <CollectionCard key={p.id} title={p.title} subtitle={'by ' + p.user.name} cover={p.covers.small}
                                    count={p.track_count} href={p.url} />
                            ))}
                        </div>
                    </section>
                ) : null}
            </div>
        </div>
    );
}

ArtistFavouritesPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
