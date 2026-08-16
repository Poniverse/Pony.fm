import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { Music, Upload } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';
import { Button } from '@/design-system/core/Button';
import { TrackList } from '@/components/TrackList';
import { AnnouncementBanner, type AnnouncementData } from '@/components/AnnouncementBanner';
import { openLogin } from '@/lib/auth';
import type { SharedProps, TrackSummary } from '@/lib/types';

interface HomeProps {
    recentTracks: TrackSummary[];
    popularTracks: TrackSummary[];
    announcement: AnnouncementData | null;
}

export default function HomePage({ recentTracks, popularTracks, announcement }: HomeProps) {
    const { auth } = usePage<SharedProps>().props;
    const user = auth.user;

    return (
        <div className="grid max-w-(--content-max) gap-7 px-7 pt-6 pb-12">
            <Head title={user ? 'Dashboard' : 'Pony.fm - Free Pony Music Hosting'} />
            <AnnouncementBanner announcement={announcement} />

            {!user ? (
                <section>
                    <div className="mb-[18px] grid gap-2">
                        <h1 className="text-4xl font-light leading-tight">
                            Pony fan music, all in one place.
                        </h1>
                        <p className="m-0 max-w-[640px] text-lg leading-(--leading-normal) text-foreground">
                            Pony.fm is a community, hosting service, and music database rolled into one, with a generous dash of pony on top.
                        </p>
                        <p className="m-0 max-w-[640px] text-sm text-muted-foreground">
                            The brony fan music community is diverse, spanning genres from symphonic metal to trance and everything in between.{' '}
                            <Link href="/about">What exactly is Pony.fm, anyway?</Link>
                        </p>
                    </div>
                    <div className="flex gap-2.5">
                        <Button size="lg" icon={Upload} onClick={openLogin}>Upload your music</Button>
                        <Button render={<Link href="/tracks" />} size="lg" variant="secondary" icon={Music}>Browse the catalogue</Button>
                    </div>
                </section>
            ) : null}

            <div className="grid grid-cols-[repeat(auto-fit,minmax(340px,1fr))] gap-7">
                <section>
                    <SectionHeader title="The newest tunes" action={<Link href="/tracks">See all</Link>} />
                    <TrackList tracks={recentTracks} />
                </section>
                <section>
                    <SectionHeader title="What's popular today" action={<Link href="/tracks/popular">See all</Link>} />
                    <TrackList tracks={popularTracks} />
                </section>
            </div>
        </div>
    );
}

HomePage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
