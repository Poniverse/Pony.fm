import React from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import {
    Bell, ChevronDown, CircleHelp, ExternalLink, Headphones, Info, LayoutGrid, List, LogOut,
    Menu, Moon, Music, Plus, Settings, Sun, Upload, User, Users,
} from 'lucide-react';
import { SidebarNav, type SidebarSection } from '@/design-system/navigation/SidebarNav';
import { SearchBox } from '@/design-system/navigation/SearchBox';
import { SearchResults } from '@/components/SearchResults';
import { Toaster } from '@/design-system/primitives/toast';
import { PlayerBar } from '@/design-system/music/PlayerBar';
import { QueuePanel } from '@/design-system/music/QueuePanel';
import { Avatar } from '@/design-system/core/Avatar';
import { IconButton } from '@/design-system/core/IconButton';
import { Button } from '@/design-system/core/Button';
import { Popover } from '@/design-system/feedback/Popover';
import { PlayerProvider, usePlayer, usePlayerTime } from '@/lib/player/PlayerContext';
import { CreditsDialog } from '@/components/CreditsDialog';
import { TrackContextMenu } from '@/components/TrackContextMenu';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/design-system/primitives/dropdown-menu';
import { PlaylistDialog } from '@/components/PlaylistDialog';
import { SignInPrompt } from '@/components/SignInPrompt';
import { useTheme } from '@/hooks/useTheme';
import { MOBILE_QUERY, useMediaQuery } from '@/hooks/useMediaQuery';
import { useSearch } from '@/hooks/useSearch';
import { useNotifications } from '@/hooks/useNotifications';
import { usePinnedPlaylists } from '@/hooks/usePinnedPlaylists';
import { formatDuration, timeAgo } from '@/lib/format';
import { openLogin } from '@/lib/auth';
import type { SharedProps, TrackSummary } from '@/lib/types';
import type { Track as DesignTrack } from '@/design-system/music/TrackRow';
import { cn } from '@/lib/utils';


export function toDesignTrack(t: TrackSummary): DesignTrack {
    return {
        id: t.id,
        title: t.title,
        artist: t.user.name,
        genre: t.genre?.name,
        cover: t.covers.thumbnail,
        duration: formatDuration(t.duration),
        isVocal: t.is_vocal,
        stats: t.stats,
    };
}

/** The bottom player bar. Isolated so usePlayerTime()'s ~4 Hz updates while
 *  audio plays re-render only this component, not the whole Shell. */
function PlayerDock({ queueOpen, onToggleQueue }: { queueOpen: boolean; onToggleQueue: () => void }) {
    const player = usePlayer();
    const time = usePlayerTime();
    const current = player.current;
    return (
        <PlayerBar
            position="bottom"
            track={current ? toDesignTrack(current) : undefined}
            playing={player.isPlaying}
            progress={time.progress}
            buffered={time.buffered}
            elapsed={formatDuration(time.elapsed)}
            duration={formatDuration(time.duration || (current ? Number(current.duration) : 0))}
            repeat={player.repeatMode !== 'off'}
            repeatOne={player.repeatMode === 'one'}
            onPlayPause={player.playPause}
            onNext={player.playNext}
            onPrev={player.playPrev}
            onSeek={player.seekTo}
            onToggleRepeat={player.toggleRepeat}
            queueOpen={queueOpen}
            onToggleQueue={onToggleQueue}
            volume={player.volume}
            onVolume={player.setVolume}
            href={current?.url}
            wrapNowPlaying={current ? (node) => (
                <TrackContextMenu track={current} onPlayNow={() => player.playAt(player.index)}>
                    {node}
                </TrackContextMenu>
            ) : undefined}
        />
    );
}

function Shell({ children }: { children: React.ReactNode }) {
    const { auth, environment } = usePage<SharedProps>().props;
    const user = auth.user;
    const player = usePlayer();
    const { theme, toggle: toggleTheme } = useTheme();
    const search = useSearch();
    const notifications = useNotifications(!!user);
    const pinned = usePinnedPlaylists(!!user);
    const [panel, setPanel] = React.useState<'queue' | 'notifications' | null>(null);
    const [creditsOpen, setCreditsOpen] = React.useState(false);
    const [newPlaylistOpen, setNewPlaylistOpen] = React.useState(false);
    const isMobile = useMediaQuery(MOBILE_QUERY);
    const [sidebarOpen, setSidebarOpen] = React.useState(false);
    const page = usePage();

    // Off-canvas sidebar and the search panel close whenever a navigation
    // starts, matching the old behaviour.
    React.useEffect(() => router.on('start', () => { setSidebarOpen(false); search.clear(); }), []);

    const togglePanel = (id: 'queue' | 'notifications') => {
        setPanel((v) => {
            const next = v === id ? null : id;
            if (next === 'notifications') notifications.markAllAsRead();
            return next;
        });
    };

    const path = page.url.split('?')[0];
    const section = '/' + path.split('/')[1];
    // The API returns absolute URLs; the sidebar treats anything with a
    // protocol as an external link.
    const pinnedItems = pinned.playlists.map((p) => {
        const href = new URL(p.url, 'http://x').pathname;
        return {
            label: p.title,
            icon: List,
            href,
            active: path === href,
            count: p.track_count,
        };
    });
    const onPinnedPlaylist = pinnedItems.some((p) => p.active);
    const sections: SidebarSection[] = [
        {
            items: [
                { label: 'Home', icon: Headphones, href: '/', active: path === '/' || path === '/dashboard' },
                { label: 'Tracks', icon: Music, href: '/tracks', active: section === '/tracks' },
                { label: 'Albums', icon: LayoutGrid, href: '/albums', active: section === '/albums' },
                { label: 'Playlists', icon: List, href: '/playlists', active: section === '/playlists' || (section === '/playlist' && !onPinnedPlaylist) },
                { label: 'Artists', icon: Users, href: '/artists', active: section === '/artists' },
            ],
        },
        {
            title: 'Site',
            items: [
                { label: 'About', icon: Info, href: '/about', active: path === '/about' },
                { label: 'FAQ', icon: CircleHelp, href: '/faq', active: path === '/faq' },
                { label: 'Forum', icon: ExternalLink, href: 'https://mlpforums.com/forum/42-music/' },
            ],
        },
        // On mobile the top bar is just burger + search; the upload action
        // and theme toggle live in the menu instead.
        ...(isMobile
            ? [{
                items: [
                    ...(user ? [{ label: 'Upload music', icon: Upload, href: '/' + user.slug + '/account/uploader' }] : []),
                    { label: theme === 'dark' ? 'Light theme' : 'Dark theme', icon: theme === 'dark' ? Sun : Moon, onClick: toggleTheme },
                ],
            }]
            : []),
        ...(user
            ? [{
                title: 'Playlists',
                grow: true,
                action: { icon: Plus, label: 'New playlist', onClick: () => setNewPlaylistOpen(true) },
                items: pinnedItems,
            }]
            : []),
        ...(user?.is_admin
            ? [{ title: 'Staff', items: [{ label: 'Admin area', icon: Settings, href: '/admin', active: section === '/admin' }] }]
            : []),
    ];

    const sidebar = (
        <SidebarNav
                sections={sections}
                logo={
                    <Link
                        href="/"
                        className="flex h-(--topbar-height) items-center bg-(--pfm-purple-deep) px-5"
                    >
                        <img
                            src="/images/ponyfm-logo-white.svg"
                            alt="Pony.fm"
                            // 32px so the disc matches the sm avatar in the
                            // account row below — they share the same left inset.
                            className="block h-8 w-auto"
                        />
                    </Link>
                }
                below={
                    <p className="m-0 px-3 text-2xs leading-snug text-faint">
                        Dedicated to nlaq<br />(1992 - 2024)
                    </p>
                }
                subheader={
                    <div className="flex items-center gap-2 border-b border-border-subtle px-3 py-2.5">
                        {user ? (
                            <>
                                <DropdownMenu>
                                    <DropdownMenuTrigger render={<button type="button" className="flex min-w-0 flex-1 cursor-pointer items-center gap-2.5 rounded-sm border-none bg-transparent px-2 py-1.5 text-left transition-[background] duration-(--dur-fast) ease-(--ease-standard) hover:bg-surface-hover" />}>
                                        <Avatar src={user.avatars.small} name={user.name} size="sm" />
                                        <span className="min-w-0 flex-1 truncate font-text text-sm font-semibold text-heading">{user.name}</span>
                                        <ChevronDown className="size-2.5 flex-none text-faint" aria-hidden="true" />
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="start" className="w-48">
                                        <DropdownMenuItem render={<Link href={'/' + user.slug} className="no-underline" />}>
                                            <User aria-hidden="true" /> Profile
                                        </DropdownMenuItem>
                                        <DropdownMenuItem render={<Link href={'/' + user.slug + '/account'} className="no-underline" />}>
                                            <Settings aria-hidden="true" /> Settings
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem onClick={() => router.post('/auth/logout')}>
                                            <LogOut aria-hidden="true" /> Log out
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                                <span className="relative inline-flex">
                                    <span className="relative inline-flex">
                                        <IconButton icon={Bell} label="Notifications" active={panel === 'notifications'} onClick={() => togglePanel('notifications')} />
                                        {notifications.unreadCount > 0 ? (
                                            <span className="pointer-events-none absolute top-[1px] right-[1px] h-[15px] min-w-[15px] rounded-pill bg-status-danger px-[3px] text-center font-mono text-[10px] leading-[15px] text-white">
                                                {notifications.unreadCount}
                                            </span>
                                        ) : null}
                                    </span>
                                    <Popover open={panel === 'notifications'} title="Notifications" placement="right-top" width={330} onClose={() => setPanel(null)}>
                                        <div className="grid gap-2">
                                            {notifications.notifications.length === 0 ? (
                                                <span className="px-2 py-[18px] text-center text-sm text-muted-foreground">Nothing yet — go listen to something!</span>
                                            ) : notifications.notifications.map((n) => (
                                                <Link key={n.id} href={n.url} onClick={() => setPanel(null)}
                                                    className={cn(
                                                        'flex cursor-pointer items-center gap-[11px] rounded-sm border border-solid bg-surface-2 px-3 py-2.5 no-underline',
                                                        n.is_read ? 'border-border-subtle' : 'border-purple-500',
                                                    )}>
                                                    <Avatar src={n.thumbnail_url} name="" size="sm" />
                                                    <span className="min-w-0 flex-1 text-sm text-foreground">
                                                        {n.text}
                                                        <span className="mt-0.5 block text-2xs text-faint">{timeAgo(n.date)}</span>
                                                    </span>
                                                </Link>
                                            ))}
                                        </div>
                                    </Popover>
                                </span>
                            </>
                        ) : (
                            <div className="flex flex-1 gap-2 px-2 py-0.5">
                                <Button size="sm" block onClick={openLogin}>Login</Button>
                                <Button size="sm" block variant="secondary" onClick={() => { window.location.href = '/register'; }}>Register</Button>
                            </div>
                        )}
                    </div>
                }
                footer={
                    <div className="flex items-center gap-2">
                        <a href="https://poniverse.net" className="grid flex-1 gap-[5px] text-2xs text-faint">
                            <span>A community by</span>
                            <img src="/images/poniverse.svg" alt="Poniverse" className={cn('w-[88px]', theme === 'light' && 'invert-[0.7]')} />
                        </a>
                        <button type="button" onClick={() => setCreditsOpen(true)} className="cursor-pointer border-none bg-transparent p-0 text-2xs text-faint">
                            Credits
                        </button>
                    </div>
                }
            />
    );

    return (
        <div className="flex h-full min-h-0 bg-background">
            {isMobile ? (
                <>
                    <div onClick={() => setSidebarOpen(false)} aria-hidden="true"
                        className={cn(
                            'fixed inset-0 z-[1080] bg-black/50 transition-[opacity,visibility] duration-(--dur-normal) ease-(--ease-standard)',
                            sidebarOpen ? 'visible opacity-100' : 'invisible opacity-0',
                        )} />
                    <div className={cn(
                        'fixed top-0 bottom-0 left-0 z-[1090] transition-transform duration-(--dur-normal) ease-(--ease-standard) will-change-transform',
                        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                    )}>
                        {sidebar}
                    </div>
                </>
            ) : sidebar}

            <div className="flex min-w-0 flex-1 flex-col">
                <div className={cn(
                    'flex h-(--topbar-height) flex-none items-center border-b border-border-subtle bg-surface-1',
                    isMobile ? 'gap-2 px-3.5' : 'gap-3.5 px-7',
                )}>
                    {isMobile ? <IconButton icon={Menu} label="Open menu" onClick={() => setSidebarOpen(true)} /> : null}
                    <div className={cn('flex-1', !isMobile && 'max-w-[420px]')}>
                        <SearchBox
                            value={search.query}
                            onChange={(e) => search.setQuery(e.target.value)}
                            panel={search.results ? <SearchResults results={search.results} /> : null}
                        />
                    </div>
                    {!isMobile && environment !== 'production' ? (
                        <span className="text-2xs uppercase tracking-caps text-status-warning">{environment}</span>
                    ) : null}
                    {!isMobile ? (
                        <span className="ml-auto flex items-center gap-2">
                            {user ? (
                                <Button render={<Link href={'/' + user.slug + '/account/uploader'} />} icon={Upload} className="rounded-pill px-[18px]">
                                    Upload music
                                </Button>
                            ) : null}
                            <IconButton
                                round
                                icon={theme === 'dark' ? Sun : Moon}
                                label={theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'}
                                onClick={toggleTheme}
                            />
                        </span>
                    ) : null}
                </div>

                <div className="flex min-h-0 flex-1">
                    <main className="min-w-0 flex-1 overflow-y-auto">
                        {children}
                    </main>
                    {panel === 'queue' ? (
                        <div className={cn('flex', isMobile && 'fixed top-0 right-0 bottom-(--nowplaying-height) z-[1070] max-w-[88vw] shadow-pop')}>
                            <QueuePanel
                                items={player.queue.map(toDesignTrack)}
                                itemIds={player.queue.map((q) => q.queueId)}
                                currentIndex={player.queue.length ? player.index : undefined}
                                onPlay={(_t, i) => player.playAt(i)}
                                onReorder={player.moveInQueue}
                                wrapRow={(row, i) => (
                                    <TrackContextMenu track={player.queue[i]} onPlayNow={() => player.playAt(i)} onRemoveFromQueue={() => player.removeFromQueue(i)}>
                                        {row}
                                    </TrackContextMenu>
                                )}
                                onClose={() => setPanel(null)}
                            />
                        </div>
                    ) : null}
                </div>

                <PlayerDock queueOpen={panel === 'queue'} onToggleQueue={() => togglePanel('queue')} />
            </div>
            <CreditsDialog open={creditsOpen} onClose={() => setCreditsOpen(false)} />
            <PlaylistDialog open={newPlaylistOpen} onClose={() => setNewPlaylistOpen(false)} onSaved={() => pinned.refresh()} />
            <SignInPrompt />
        </div>
    );
}

export default function AppLayout({ children }: { children: React.ReactNode }) {
    return (
        <PlayerProvider>
            <Toaster>
                <Shell>{children}</Shell>
            </Toaster>
        </PlayerProvider>
    );
}
