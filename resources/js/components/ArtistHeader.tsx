import React from 'react';
import { usePage } from '@inertiajs/react';
import { FolderOpen, Settings, Star } from 'lucide-react';
import { Avatar } from '@/design-system/core/Avatar';
import { Badge } from '@/design-system/core/Badge';
import { Tabs } from '@/design-system/navigation/Tabs';
import { FollowButton } from '@/components/FollowButton';
import type { ArtistData, SharedProps } from '@/lib/types';

/**
 * Profile banner (avatar-colour gradient, as on the old site) + tab strip
 * shared by the profile / content / favourites pages.
 */
export function ArtistHeader({ artist, active }: { artist: ArtistData; active: 'profile' | 'content' | 'favourites' }) {
    const { auth } = usePage<SharedProps>().props;
    const [c1, c2] = [artist.avatar_colors[0] ?? '#84528a', artist.avatar_colors[1] ?? '#2e1c31'];
    const isOwn = auth.user?.id === artist.id;

    const base = '/' + artist.slug;
    const tabs = [
        { id: 'profile', label: 'Profile', href: base },
        { id: 'content', label: 'Content', href: base + '/content' },
        { id: 'favourites', label: 'Favourites', href: base + '/favourites' },
        ...(isOwn || artist.permissions.edit ? [{ id: 'account', label: 'Manage account', icon: Settings, href: base + '/account' }] : []),
    ];

    return (
        <header className="grid gap-0">
            <div className="px-7 pt-9 pb-6" style={{ background: `linear-gradient(120deg, ${c1}, ${c2})` }}>
                <div className="flex flex-wrap items-end gap-[18px]">
                    <Avatar src={artist.avatars.normal} name={artist.name} size="xl" square />
                    <div className="grid min-w-0 flex-1 gap-[7px]">
                        <div className="flex flex-wrap items-center gap-2">
                            {artist.isAdmin ? <Badge tone="brand" icon={Star}>Staff</Badge> : null}
                            {artist.is_archived ? <Badge tone="warning" icon={FolderOpen}>Archived profile</Badge> : null}
                        </div>
                        <h1 className="text-3xl font-semibold leading-tight text-white [text-shadow:0_1px_8px_rgba(0,0,0,0.4)]">
                            {artist.name}
                        </h1>
                        <span className="text-sm text-white/85 [text-shadow:0_1px_6px_rgba(0,0,0,0.4)]">
                            {artist.followers === 1 ? '1 follower' : artist.followers.toLocaleString() + ' followers'}
                        </span>
                    </div>
                    <div className="flex gap-2">
                        {!artist.is_archived ? <FollowButton artistId={artist.id} initialFollowing={artist.user_data.is_following} /> : null}
                    </div>
                </div>
            </div>
            <div className="bg-surface-1 px-7">
                <Tabs tabs={tabs} active={active} />
            </div>
        </header>
    );
}
