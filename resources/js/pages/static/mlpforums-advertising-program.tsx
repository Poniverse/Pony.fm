import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';

const P = 'm-0 text-md leading-(--leading-normal) text-foreground';
const OL = 'm-0 grid gap-2.5 pl-[22px] text-md leading-(--leading-normal) text-foreground';

export default function AdvertisingPage() {
    return (
        <article className="grid max-w-[760px] gap-8 px-7 pt-8 pb-14">
            <Head title="MLP Forums Advertising Program" />
            <header>
                <h1 className="m-0 text-4xl font-light leading-tight">MLP Forums Advertising Program</h1>
            </header>

            <section className="grid gap-3">
                <SectionHeader title="What does Pony.fm want from musicians?" />
                <p className={P}>Want <em>from</em> probably isn't the best way to put it. Pony.fm wants to provide brony musicians with the best package of services for hosting their music. We also want to provide musicians with free advertising space right on the homepage of <a href="https://mlpforums.com/" title="MLP Forums" target="_blank" rel="noreferrer">MLP Forums</a> to be seen by its userbase of over 32,000 registered members. And we want to provide your listeners with the best experience to listen to your music. All we ask in exchange is for musicians to use Pony.fm as their home base of sorts for their music hosting needs. We understand musicians want to continue to upload their music to a wide array of music hosting sites, and we can respect that. All we ask is that you use your Pony.fm link any time you send someone to download or listen to a song.</p>
                <p className={P}><strong>Sound like an interesting proposal? Then read on!</strong></p>
            </section>

            <section className="grid gap-3">
                <SectionHeader title="Why should I host my music on Pony.fm?" />
                <p className={P}>We understand brony musicians have a lot of choices when it comes to where to host their music. But Pony.fm is engineered specifically to be the best site for <em>My Little Pony</em> fan music!</p>
                <p className={P}>But why, specifically, should you choose Pony.fm? The better question is: why wouldn't you?</p>
                <p className={P}>Pony.fm exclusively hosts <em>My Little Pony</em> fan content! Ask yourself, how exactly would a user with no knowledge of your music find you from the SoundCloud or Bandcamp homepage? A search for Pony music will yield thousands of unspecific results. A genre search will fill the page with music unrelated to bronies. Unless the user is looking for your song specifically, it's difficult for them to find it.</p>
                <p className={P}>Pony.fm addresses this problem by removing the non-brony music from the equation entirely, allowing users to search for genres, styles, or even specify a specific show song they want to hear a remix of and get nothing but relevant results. This means Pony.fm's listeners spend less time searching for your music and more time listening to it!</p>
                <p className={P}>Pony.fm also provides you with features that sites like SoundCloud and Bandcamp don't. With Pony.fm, you can offer streaming and unlimited downloads. You'll never run out of free downloads again!</p>
                <p className={P}>Other artists have turned to file sharing sites for music downloads due to the limitations of sites like SoundCloud, but because of the risks of unknown downloads, many users simply will not trust links to file sharing sites. File sharing sites also have limitations. Since they host specific files, you're forced to provide an additional upload and link for every individual file type and quality you want to offer listeners. These sites are also designed only for linking to files from an external site, eliminating any chance that users will find your music through the file sharing site itself. On Pony.fm your listeners will be exposed to your branding, your music, your tags, and much more allowing you to get the most out of every valuable click to your download.</p>
                <p className={P}>Sites like YouTube aren't designed to be used as music hosting sites, playing significantly reduced quality lossy audio files to allow for the bandwidth of the video. Pony.fm, however, is designed for music, providing high quality music streams to your listeners. <strong>Pony.fm is all around the best balance of features designed specifically for brony musicians.</strong></p>
                <p className={P}>Beyond everything else, using Pony.fm brings you into the Poniverse supercommunity! Not only will users be able to give you valuable feedback right on Pony.fm's comment system, but with one login, you'll unlock all the features of the Poniverse network. Want to show off your music to MLP Forum's 32,000+ members? How would you like to get tips and tricks from fellow musicians in our Creative Resources area? Or would you just like to relax and spend some time talking about your favorite episode? Want to host a live-stream on Equestria.tv? All of that and much more is possible when you unlock the power of your Poniverse login! <strong>With Poniverse, Pony.fm isn't just a music hosting or download site - it's a community made for bronies, by bronies!</strong></p>
            </section>

            <section className="grid gap-3">
                <SectionHeader title="All right, I get it; it's got some nice features. I'll upload some songs. But what's this about linking to my songs on Pony.fm? Why should I?" />
                <p className={P}>So you want more than just great features? We respect that, because we want more, too. Pony.fm already houses the largest collection of brony music on the planet as the official host of the MLP Music Archive, but we want to bring Pony.fm to the next level as the go-to place for people to find and post music in this fandom, and we need your support as a musician to do it!</p>
                <p className={P}>Every time you post a download link on YouTube or share a link to a song on Twitter, Facebook, or any other site, you're deciding which experience you want to give your listeners. We think Pony.fm provides the best experience for bronies to listen to fan music and we want you to share in that belief!</p>
                <p className={P}>So we're offering a special incentive to those that go the extra mile and help share Pony.fm with the community by using the Pony.fm link to their song whenever possible! <strong>We're <em>giving</em> you a banner ad right smack on the front page of Poniverse's largest site for absolutely free.</strong></p>
                <p className={P}><em>Why?</em> Because we want the members of MLP Forums to listen to your music on Pony.fm just as bad as you do! That advertisement to your music is also an advertisement to try Pony.fm, so it benefits everybody involved! You get users being sent directly to your music page on Pony.fm, we get new users checking out Pony.fm that are interested in your music, and the listener gets an awesome new site to find pony tunes on!</p>
            </section>

            <section className="grid gap-3">
                <SectionHeader title="Okay… that's cool. I'm in! What do I need to do?" />
                <p className={P}>It's super simple!</p>
                <ol className={OL}>
                    <li><Link href="/account/uploader">Upload your music to Pony.fm.</Link></li>
                    <li>If you've previously linked to song downloads to SoundCloud, Bandcamp, or a file sharing service in the description of YouTube videos, consider switching those links over to Pony.fm to ensure the best possible experience for your listeners!</li>
                    <li className="grid gap-3">
                        <p className={P}>Once it's uploaded, fill out <a href="https://goo.gl/forms/hmTUVjKNJj" target="_blank" rel="noreferrer">this form</a>. This gets the ball rolling on getting you free advertising. To further expedite the process of getting you ad-space, include a 540x200 image for your advertisement like this one:</p>
                        <p className={P + ' text-center'}><img src="https://mlpforums.com/uploads/referral-0017832001448500924.gif" alt="Example advertisement banner" className="max-w-full" /></p>
                        <p className={P}>Note: the ad will be scaled down to 270x100 pixels when displayed on MLP Forums).</p>
                    </li>
                    <li>Wait for a reply! We'll review your application and get back to you as soon as possible with confirmation that you'll be included in the program and more details! If you didn't provide an advertisement or if there are any issues with your advertisement image, we'll help you get one ready to go at this stage!</li>
                    <li>Once your advertisement is up, start using your Pony.fm links anywhere you link to a song!</li>
                </ol>
            </section>

            <section className="grid gap-3">
                <SectionHeader title="I've got to admit it sounds neat, but I'm skeptical. Who can I contact for more information or answers to my questions?" />
                <p className={P}>Just shoot a message to Poniverse's Head of Public Relations, Mercury, at <a href="mailto:mercury@poniverse.net" target="_blank" rel="noreferrer">mercury@poniverse.net</a> and she'll be happy to answer any questions you have about Pony.fm and the advertising deal offered here!</p>
            </section>
        </article>
    );
}

AdvertisingPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
