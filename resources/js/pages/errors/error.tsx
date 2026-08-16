import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { CircleHelp, Clock, House, Lock, TriangleAlert } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { EmptyState } from '@/design-system/feedback/EmptyState';
import { Button } from '@/design-system/core/Button';

const MESSAGES: Record<number, { title: string; body: string; icon: LucideIcon }> = {
    400: { title: 'Bad request', body: "Whatever you just asked for, it wasn't right. Try something else?", icon: TriangleAlert },
    403: { title: 'Not so fast!', body: "You don't have permission to see this. If you think you should, try logging in.", icon: Lock },
    404: { title: 'Page not found', body: "This page must've been taken by the Mirror Pool. It doesn't exist — or it moved.", icon: CircleHelp },
    500: { title: 'Something broke', body: 'Our fault, not yours. The error has been noted — try again in a moment.', icon: TriangleAlert },
    503: { title: 'Down for maintenance', body: "Pony.fm is having a quick spa day. We'll be right back.", icon: Clock },
};

export default function ErrorPage({ status }: { status: number }) {
    const m = MESSAGES[status] ?? MESSAGES[500];
    return (
        <div className="grid h-full place-items-center p-7">
            <Head title={m.title} />
            <div className="w-full max-w-[520px]">
                <EmptyState icon={m.icon} title={status + ' — ' + m.title} action={<Link href="/"><Button variant="secondary" icon={House}>Back home</Button></Link>}>
                    {m.body}
                </EmptyState>
            </div>
        </div>
    );
}

ErrorPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
