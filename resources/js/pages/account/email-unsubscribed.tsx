import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { CircleCheck, House } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { EmptyState } from '@/design-system/feedback/EmptyState';
import { Button } from '@/design-system/core/Button';
import { unsubscribeMessage } from '@/lib/unsubscribe';

interface EmailUnsubscribedProps {
    unsubscribedMessageKey: number | null;
    unsubscribedUser: string | null;
}

export default function EmailUnsubscribedPage({ unsubscribedMessageKey, unsubscribedUser }: EmailUnsubscribedProps) {
    const message = unsubscribedMessageKey != null
        ? unsubscribeMessage(Number(unsubscribedMessageKey), unsubscribedUser)
        : null;
    return (
        <div className="grid h-full place-items-center p-7">
            <Head title="Unsubscribed" />
            <div className="w-full max-w-[560px]">
                <EmptyState icon={CircleCheck} title="Unsubscribed"
                    action={<Link href="/"><Button variant="secondary" icon={House}>Back home</Button></Link>}>
                    {message ?? 'You have been unsubscribed from that email notification.'}
                </EmptyState>
            </div>
        </div>
    );
}

EmailUnsubscribedPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
