import React from 'react';
import { Announcement } from '@/design-system/feedback/Announcement';

export interface AnnouncementData {
    id: number;
    title: string;
    text_content: string;
    announcement_type_id: number;
    links?: { text: string; url: string }[] | null;
}

const TONES: Record<number, 'simple' | 'alert' | 'serious'> = {
    1: 'simple',
    2: 'alert',
    3: 'serious',
};

export function AnnouncementBanner({ announcement }: { announcement: AnnouncementData | null }) {
    const [dismissed, setDismissed] = React.useState(false);
    if (!announcement || dismissed) return null;
    return (
        <Announcement
            tone={TONES[announcement.announcement_type_id] ?? 'simple'}
            title={announcement.title}
            onDismiss={() => setDismissed(true)}
            actions={announcement.links?.length ? (
                <span className="flex gap-4">
                    {announcement.links.map((l, i) => (
                        <a key={i} href={l.url} className="text-white underline">{l.text}</a>
                    ))}
                </span>
            ) : undefined}
        >
            {announcement.text_content}
        </Announcement>
    );
}
