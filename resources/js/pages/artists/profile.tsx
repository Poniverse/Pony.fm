import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';
import { ArtistHeader } from '@/components/ArtistHeader';
import { TrackList } from '@/components/TrackList';
import { CommentsSection } from '@/components/CommentsSection';
import { Markdown } from '@/lib/markdown';
import type { ArtistData, CommentData, TrackSummary } from '@/lib/types';

interface ArtistProfileProps {
    artist: ArtistData;
    latestTracks: TrackSummary[];
    comments: CommentData[];
}

export default function ArtistProfilePage({ artist, latestTracks, comments }: ArtistProfileProps) {
    return (
        <div>
            <Head title={artist.name} />
            <ArtistHeader artist={artist} active="profile" />
            <div className="detail-columns max-w-(--content-max) px-7 pt-6 pb-12 [--detail-aside:340px]">
                <div className="grid gap-7">
                    {artist.bio ? (
                        <section>
                            <SectionHeader title="About" level={2} />
                            <div className="max-w-[640px] text-md text-foreground">
                                <Markdown source={artist.bio} />
                            </div>
                        </section>
                    ) : null}
                    <section>
                        <SectionHeader title="Latest tracks" count={latestTracks.length} />
                        <TrackList tracks={latestTracks} />
                    </section>
                </div>
                <aside className="grid gap-6">
                    <CommentsSection type="user" id={artist.id} initial={comments} />
                </aside>
            </div>
        </div>
    );
}

ArtistProfilePage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
