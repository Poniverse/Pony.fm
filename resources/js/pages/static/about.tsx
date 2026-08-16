import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Music, Upload } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';
import { Button } from '@/design-system/core/Button';
import type { SharedProps } from '@/lib/types';
import { openLogin } from '@/lib/auth';

const P = 'm-0 text-md leading-(--leading-normal) text-foreground';

export default function AboutPage() {
    const { auth } = usePage<SharedProps>().props;
    const upload = () => {
        if (auth.user) router.visit('/' + auth.user.slug + '/account/uploader');
        else openLogin();
    };
    return (
        <article className="grid max-w-[760px] gap-8 px-7 pt-8 pb-14">
            <Head title="About" />
            <header className="grid gap-3">
                <h1 className="text-4xl font-light leading-tight">What exactly is Pony.fm, anyway?</h1>
                <p className={P}>Some My Little Pony: Friendship is Magic fans - typically referred to as "bronies" are the musical type, and show their appreciation for the show by pouring their talent into fan music.</p>
                <p className={P}>The brony fan music community is diverse, spanning genres from symphonic metal to trance and everything in between. But most importantly, the community creates music.</p>
                <p className="m-0 font-display text-2xl font-semibold leading-(--leading-normal) text-brand-text">A lot of music.</p>
                <p className={P}>All this music has to go somewhere. YouTube, SoundCloud, and Bandcamp are popular outlets that many brony musicians use to host their tunes. But no mainstream sites are specifically designed for our fandom's needs, and they're not particularly helpful if, as a listener, you're looking for pony fan music.</p>
                <p className={P}>That's where Pony.fm comes in. Pony.fm is a community, hosting service, and music database rolled into one, with a generous dash of pony on top.</p>
            </header>

            <section className="grid gap-3">
                <SectionHeader title="So it's SoundCloud with ponies?" />
                <p className="m-0 font-display text-xl font-semibold leading-(--leading-normal) text-heading">Eenope!</p>
                <p className={P}>Pony.fm is an original project. Although it takes inspiration from a number of well-known services for the general public, Pony.fm is not specifically modeled after any one of them. As a fan site itself, Pony.fm is an experience all its own.</p>
            </section>

            <section className="grid gap-3">
                <SectionHeader title="What makes Pony.fm special?" />
                <p className={P}>Pony.fm is a service created by bronies, for bronies. Every inch of the Pony.fm experience is crafted with ponies and bronies in mind. Some of the features necessarily resemble what you may find on other sites - lossless uploads, for example - but some features are specific to the pony fan music community.</p>
                <p className={P}>Created as a service for the fandom, Pony.fm aims to be the one-stop shop for all things pony music, for artists and listeners alike.</p>
            </section>

            <section className="grid gap-3">
                <SectionHeader title="What does MLP Forums have to do with Pony.fm?" />
                <p className={P}>MLP Forums and Pony.fm share an owner, and each encompasses a different segment of the global My Little Pony: Friendship is Magic community. Put together, both sites are able to offer a richer "supercommunity" experience than either site could offer on its own.</p>
            </section>

            <section className="grid gap-3">
                <SectionHeader title="Who is behind Pony.fm?" />
                <p className={P}>Pony.fm is built and run by the same people behind MLP Forums and Poniverse.net.</p>
                <div className="flex items-center gap-3.5 pt-1.5">
                    <img src="/images/poniverse.svg" alt="Poniverse" className="w-[116px]" />
                    <span className="text-xs text-faint">We're open-source — pull requests welcome.</span>
                </div>
            </section>

            <footer className="flex gap-2.5 pt-1">
                <Button render={<Link href="/tracks" />} size="lg" icon={Music}>Browse the catalogue</Button>
                <Button size="lg" variant="secondary" icon={Upload} onClick={upload}>Upload your music</Button>
            </footer>
        </article>
    );
}

AboutPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
