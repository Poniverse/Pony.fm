import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';

const P = 'm-0 text-md leading-(--leading-normal) text-foreground';
const UL = 'm-0 grid gap-1.5 pl-[22px] text-md leading-(--leading-normal) text-foreground';

export default function FaqPage() {
    return (
        <article className="grid max-w-[760px] gap-8 px-7 pt-8 pb-14">
            <Head title="FAQ" />
            <header>
                <h1 className="m-0 text-4xl font-light leading-tight">Pony.fm FAQ</h1>
            </header>

            <section className="grid gap-3">
                <SectionHeader title="Why doesn't Pony.fm support MP3 uploads?" />
                <p className={P}>MP3 encoding is "lossy." Lossy means that, during the encoding process, quality gets sacrificed for a decrease in size.</p>
                <p className={P}>Pony.fm accepts lossless uploads, which put a "perfect" copy of your track in Pony.fm's file store. This is offered up for download on its own for audiophiles who like CD or better-than-CD sound quality, but starting from a lossless original also allows Pony.fm to transcode a song to other lossy formats with only one degree of loss.</p>
            </section>

            <section className="grid gap-3">
                <SectionHeader title="Why isn't my file being accepted for upload?" />
                <p className={P}>Pony.fm analyzes all uploads to determine their format and check it against a whitelist; the file extension is ignored. Unfortunately, slight variations in AIFF and WAV files exist that need to be individually whitelisted.</p>
                <p className={P}>Most of these should have been nailed by now, but if yours isn't being accepted, contact <a href="mailto:mercury@poniverse.net" target="_blank" rel="noreferrer">mercury@poniverse.net</a> or <a href="mailto:support@pony.fm" target="_blank" rel="noreferrer">Pony.fm Support</a> with a copy of the file you're trying to upload and we'll sort it out!</p>
            </section>

            <section className="grid gap-3">
                <SectionHeader title="How do I upload a song?" />
                <p className={P}>At the top right of your screen there should be a button titled "Upload" next to the "send feedback" one. Select the Upload button and a drop down menu will appear, select the first option titled "Track Uploader". You should now be on a screen displaying the uploader. Select the Green button titled "Add Files" and select your song from your computer. The track should now start its download.</p>
                <p className={P}>Please be aware that Pony.fm doesn't support MP3 uploads.</p>
            </section>

            <section className="grid gap-3">
                <SectionHeader title="How do I set an avatar?" />
                <p className={P}>Avatars in Pony.fm use a free service called Gravatar. To learn more and set up your own Gravatar account, <a href="https://gravatar.com/" title="Gravatar" target="_blank" rel="noreferrer">click here</a>!</p>
            </section>

            <section className="grid gap-3">
                <SectionHeader title="Why the connection to MLP Forums?" />
                <p className={P}>MLP Forums is the web's largest <em>My Little Pony: Friendship is Magic</em> forum community. Formally linking the two sites together paves the way for a rich, cross-site community experience. Members of one site can easily jump into the other without the hassle of managing yet another account, and content can seamlessly be brought from MLP Forums to Pony.fm and vice versa in meaningful ways.</p>
            </section>

            <section className="grid gap-3">
                <SectionHeader title="How do I send feedback to the developers?" />
                <p className={P}>Three ways! Choose whichever you're most comfortable with:</p>
                <ul className={UL}>
                    <li>Post your feedback in the <a href="https://mlpforums.com/forum/62-ponyfm/" title="Pony.fm Forum" target="_blank" rel="noreferrer">official Pony.fm forum</a>.</li>
                    <li>Email <a href="mailto:mercury@poniverse.net" target="_blank" rel="noreferrer">Mercury</a>, Pony.fm's current Project Manager, or <a href="mailto:support@pony.fm" target="_blank" rel="noreferrer">Pony.fm Support</a>.</li>
                    <li>Open a <a href="https://github.com/Poniverse/Pony.fm/issues" target="_blank" rel="noreferrer">GitHub issue</a>.</li>
                </ul>
                <p className={P}>All feedback is greatly appreciated on Pony.fm and we do our hardest to keep this site functional and to keep all of you happy.</p>
            </section>

            <section className="grid gap-3">
                <SectionHeader title={'What is the "Poniverse" and what does Pony.fm have to do with it?'} />
                <p className={P}><a href="https://poniverse.net/" title="Poniverse: The Pony Supercommunity" target="_blank" rel="noreferrer">Poniverse</a> is a network that links together several pony communities like <a href="https://mlpforums.com/" title="MLP Forums" target="_blank" rel="noreferrer">MLP Forums</a>, <a href="https://canterlotavenue.com/" title="Canterlot Avenue" target="_blank" rel="noreferrer">Canterlot Avenue</a>, <a href="http://equestria.tv/" title="Equestria.tv" target="_blank" rel="noreferrer">Equestria.tv</a>, and <a href="http://poniarcade.com/" title="PoniArcade" target="_blank" rel="noreferrer">PoniArcade</a> to form a "supercommunity" with something for everypony.</p>
                <p className={P}>Pony.fm is just one of those sites and it provides brony musicians with their own special corner to share their work with others and to receive feedback from other musicians, and in lots of cases to form collaborations that can end up in great partnerships.</p>
            </section>

            <section className="grid gap-3">
                <SectionHeader title="How do I report someone?" />
                <p className={P}>Email <a href="mailto:mercury@poniverse.net" target="_blank" rel="noreferrer">mercury@poniverse.net</a> with any moderation issues you have.</p>
            </section>

            <section className="grid gap-3">
                <SectionHeader title="How do I download a song?" />
                <p className={P}>Click on the track that you are looking to download and you will notice to the right of the screen is a button titled "Downloads".</p>
                <p className={P}>Click this button and you will be brought a drop down menu with FLAC, MP3, OGG, AAC, and ALAC files for you to download.</p>
                <p className={P}>Select your preferred file type to start the download and it should all be smooth sailing from there.</p>
                <p className={P}><strong>Note:</strong> if "Downloads" button is greyed out, that means the artist has disabled downloads on that track.</p>
            </section>
        </article>
    );
}

FaqPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
