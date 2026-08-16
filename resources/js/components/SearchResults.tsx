import React from 'react';
import { Link } from '@inertiajs/react';
import { TrackList } from '@/components/TrackList';
import { CollectionCard } from '@/design-system/music/CollectionCard';
import { Avatar } from '@/design-system/core/Avatar';
import type { SearchResultsData } from '@/hooks/useSearch';

function Section({ title, count, empty, children }: { title: string; count: number; empty: string; children: React.ReactNode }) {
    return (
        <section>
            <h3 className="m-0 mb-1.5 font-text text-2xs font-semibold uppercase tracking-caps text-faint">{title}</h3>
            {count === 0
                ? <p className="m-0 rounded-sm bg-surface-2 px-3 py-2.5 text-sm text-muted-foreground">{empty}</p>
                : <div className="grid gap-1.5">{children}</div>}
        </section>
    );
}

/** Search popover content: playable tracks on the left; artists, albums and
 *  playlists on the right — the same split as the old search pullout. */
export function SearchResults({ results }: { results: SearchResultsData }) {
    return (
        <div className="grid items-start gap-5 md:grid-cols-[minmax(0,5fr)_minmax(0,4fr)]">
            <Section title="Matching tracks" count={results.tracks.length} empty="No tracks found…">
                <TrackList tracks={results.tracks} />
            </Section>
            <div className="grid gap-5">
                <Section title="Matching artists" count={results.users.length} empty="No artists found…">
                    {results.users.map((u) => (
                        <Link key={u.id} href={u.url}
                            className="flex items-center gap-2.5 rounded-sm px-1.5 py-1 no-underline transition-[background] duration-(--dur-fast) ease-(--ease-standard) hover:bg-surface-hover">
                            <Avatar src={u.avatars.small} name={u.name} size="sm" />
                            <span className="truncate font-text text-sm font-semibold text-heading">{u.name}</span>
                        </Link>
                    ))}
                </Section>
                <Section title="Matching albums" count={results.albums.length} empty="No albums found…">
                    {results.albums.map((a) => (
                        <CollectionCard key={a.id} title={a.title} subtitle={'by ' + a.user.name}
                            cover={a.covers.small} count={a.track_count} href={a.url} />
                    ))}
                </Section>
                <Section title="Matching playlists" count={results.playlists.length} empty="No playlists found…">
                    {results.playlists.map((p) => (
                        <CollectionCard key={p.id} title={p.title} subtitle={'by ' + p.user.name}
                            cover={p.covers.small} count={p.track_count} href={p.url} />
                    ))}
                </Section>
            </div>
        </div>
    );
}
