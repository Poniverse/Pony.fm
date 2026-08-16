import React from 'react';
import { Dialog } from '@/design-system/feedback/Dialog';
import { Button } from '@/design-system/core/Button';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';

const P = 'm-0 text-sm leading-normal text-foreground';
const UL = 'm-0 grid gap-1 pl-5 text-sm leading-normal text-foreground';

export function CreditsDialog({ open, onClose }: { open: boolean; onClose: () => void }) {
    return (
        <Dialog open={open} onClose={onClose} title="Credits" width={560} footer={<Button onClick={onClose}>Close</Button>}>
            <div className="grid max-h-[60vh] gap-3.5 overflow-y-auto">
                <p className={P}>Pony.fm was created to organize the <em>My Little Pony</em> community's fan music. The project is maintained by <a href="https://poniverse.net/" target="_blank" rel="noreferrer">Poniverse</a>, an organization devoted to building and operating fan sites for the pony community.</p>

                <SectionHeader title="Are you a developer?" level={3} />
                <p className={P}>Pony.fm is open-sourced under the GNU Affero General Public License (AGPL). Join the project's development at <a href="https://github.com/Poniverse/Pony.fm" target="_blank" rel="noreferrer">GitHub</a>!</p>

                <SectionHeader title="Open-source credits" level={3} />
                <p className={P}>Pony.fm would not be possible without the efforts of the open-source community. We thank the following projects, in no particular order, for providing the building blocks for our own.</p>
                <ul className={UL}>
                    <li><a href="https://laravel.com/" target="_blank" rel="noreferrer">Laravel</a> - our backend framework of choice</li>
                    <li><a href="https://www.php.net/" target="_blank" rel="noreferrer">PHP</a> - for providing a batteries-loaded language to build great web apps in</li>
                    <li><a href="https://getcomposer.org/" target="_blank" rel="noreferrer">Composer</a> - for making the management of PHP dependencies sane</li>
                    <li><a href="https://react.dev/" target="_blank" rel="noreferrer">React</a> - our front-end framework of choice</li>
                    <li><a href="https://inertiajs.com/" target="_blank" rel="noreferrer">Inertia.js</a> - for letting Laravel and React act as one app</li>
                    <li><a href="https://www.typescriptlang.org/" target="_blank" rel="noreferrer">TypeScript</a> - for catching our mistakes before you do</li>
                    <li><a href="https://vitejs.dev/" target="_blank" rel="noreferrer">Vite</a> - for being our asset pipeline</li>
                    <li><a href="https://tailwindcss.com/" target="_blank" rel="noreferrer">Tailwind CSS</a> - for styling every inch of the site</li>
                    <li><a href="https://ui.shadcn.com/" target="_blank" rel="noreferrer">shadcn/ui</a> - the foundation our design system is built on</li>
                    <li><a href="https://base-ui.com/" target="_blank" rel="noreferrer">Base UI</a> - for accessible headless components</li>
                    <li><a href="https://lucide.dev/" target="_blank" rel="noreferrer">Lucide</a> - for every icon you see</li>
                    <li><a href="https://nodejs.org/" target="_blank" rel="noreferrer">Node.js</a> - for making JavaScript useful outside the browser</li>
                    <li><a href="https://ffmpeg.org/" target="_blank" rel="noreferrer">FFmpeg</a> - for analyzing and dealing with every audio file we can throw at it</li>
                    <li><a href="https://xiph.org/flac/" target="_blank" rel="noreferrer">FLAC</a> - FLAC is best audio codec /)</li>
                    <li><a href="https://xiph.org/vorbis/" target="_blank" rel="noreferrer">OGG Vorbis</a> - a great open-source audio codec</li>
                    <li><a href="https://lame.sourceforge.io/" target="_blank" rel="noreferrer">LAME</a> - for encoding our MP3 files</li>
                    <li><a href="https://atomicparsley.github.io/" target="_blank" rel="noreferrer">AtomicParsley</a> - for making it easy to work with tags in M4A files</li>
                    <li><a href="https://www.getid3.org/" target="_blank" rel="noreferrer">getID3</a> - for reading the tags in everything you upload</li>
                    <li><a href="https://www.postgresql.org/" target="_blank" rel="noreferrer">PostgreSQL</a> - for keeping every track, play and favourite safe</li>
                    <li><a href="https://www.elastic.co/elasticsearch" target="_blank" rel="noreferrer">Elasticsearch</a> - for powering the search box</li>
                    <li><a href="https://redis.io/" target="_blank" rel="noreferrer">Redis</a> - for our queues and caches</li>
                    <li><a href="https://guzzlephp.org/" target="_blank" rel="noreferrer">Guzzle</a> - for adding sanity to the art of making HTTP requests</li>
                    <li><a href="https://github.com/VentureCraft/revisionable" target="_blank" rel="noreferrer">Revisionable</a> - for making audit logs easy</li>
                    <li><a href="https://github.com/markedjs/marked" target="_blank" rel="noreferrer">Marked</a> and <a href="https://github.com/cure53/DOMPurify" target="_blank" rel="noreferrer">DOMPurify</a> - for safe, pretty Markdown descriptions</li>
                    <li><a href="https://recharts.org/" target="_blank" rel="noreferrer">Recharts</a> - for giving us beautiful, programmable charts</li>
                    <li><a href="https://dndkit.com/" target="_blank" rel="noreferrer">dnd kit</a> - for drag-and-drop that feels right</li>
                    <li><a href="https://daypicker.dev/" target="_blank" rel="noreferrer">React DayPicker</a> and <a href="https://date-fns.org/" target="_blank" rel="noreferrer">date-fns</a> - for making dates pleasant to pick and sane to handle</li>
                    <li><a href="https://fontsource.org/" target="_blank" rel="noreferrer">Fontsource</a> - for self-hosting Josefin Sans, Karla and IBM Plex Mono</li>
                    <li><a href="https://docker.com/" target="_blank" rel="noreferrer">Docker</a> - for making dev and production environments match</li>
                </ul>
            </div>
        </Dialog>
    );
}
