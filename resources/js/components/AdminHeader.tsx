import React from 'react';
import { Tags } from 'lucide-react';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';
import { Tabs } from '@/design-system/navigation/Tabs';

export type AdminTab = 'genres' | 'tracks' | 'unclassified' | 'show-songs' | 'users' | 'announcements';

export function AdminHeader({ active }: { active: AdminTab }) {
    return (
        <div className="grid gap-3.5">
            <SectionHeader title="Admin area" />
            <Tabs active={active} tabs={[
                { id: 'genres', label: 'Genres', href: '/admin/genres' },
                { id: 'show-songs', label: 'Show songs', href: '/admin/show-songs' },
                { id: 'tracks', label: 'Tracks', href: '/admin/tracks' },
                { id: 'unclassified', label: 'Classifier', icon: Tags, href: '/admin/tracks/unclassified' },
                { id: 'users', label: 'Users', href: '/admin/users' },
                { id: 'announcements', label: 'Announcements', href: '/admin/announcements' },
            ]} />
        </div>
    );
}
