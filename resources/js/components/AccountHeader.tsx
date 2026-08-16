import React from 'react';
import { LayoutGrid, List, Music, Settings, Upload } from 'lucide-react';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';
import { Tabs } from '@/design-system/navigation/Tabs';

export type AccountTab = 'settings' | 'uploader' | 'tracks' | 'albums' | 'playlists';

export function AccountHeader({ slug, active }: { slug: string; active: AccountTab }) {
    const base = '/' + slug + '/account';
    return (
        <div className="grid gap-3.5">
            <SectionHeader title="Your account" />
            <Tabs active={active} tabs={[
                { id: 'settings', label: 'Settings', icon: Settings, href: base },
                { id: 'uploader', label: 'Upload music', icon: Upload, href: base + '/uploader' },
                { id: 'tracks', label: 'Tracks', icon: Music, href: base + '/tracks' },
                { id: 'albums', label: 'Albums', icon: LayoutGrid, href: base + '/albums' },
                { id: 'playlists', label: 'Playlists', icon: List, href: base + '/playlists' },
            ]} />
        </div>
    );
}
