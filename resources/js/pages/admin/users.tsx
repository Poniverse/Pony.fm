import React from 'react';
import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { AdminHeader } from '@/components/AdminHeader';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';
import { Button } from '@/design-system/core/Button';
import { Input } from '@/design-system/core/Input';
import { api } from '@/lib/api';

/** Archived-artist creation — accounts that hold music for artists who
 *  aren't on Pony.fm themselves. Merging accounts stays a CLI job. */
export default function AdminUsersPage() {
    const [username, setUsername] = React.useState('');
    const [busy, setBusy] = React.useState(false);
    const [message, setMessage] = React.useState<{ kind: 'ok' | 'error'; text: string } | null>(null);

    const create = () => {
        if (!username.trim() || busy) return;
        setBusy(true);
        setMessage(null);
        api.post('/artists', { username: username.trim() })
            .then(() => {
                setMessage({ kind: 'ok', text: 'Archived artist "' + username.trim() + '" created.' });
                setUsername('');
            })
            .catch((e) => {
                const errors = e.response?.data?.errors as Record<string, string[]> | undefined;
                setMessage({ kind: 'error', text: errors ? Object.values(errors).flat().join(' ') : 'Could not create that artist.' });
            })
            .finally(() => setBusy(false));
    };

    return (
        <div className="grid max-w-[720px] gap-5 px-7 pt-6 pb-12">
            <Head title="Admin - Users" />
            <AdminHeader active="users" />
            <section className="grid gap-3">
                <SectionHeader title="Create an archived artist" level={2} />
                <p className="m-0 max-w-[560px] text-sm text-muted-foreground">
                    Archived profiles hold uploads on behalf of artists who aren't on Pony.fm.
                    Account merging is done from the CLI (<code className="font-mono">php artisan</code>).
                </p>
                <form onSubmit={(e) => { e.preventDefault(); create(); }} className="flex max-w-[420px] items-end gap-2">
                    <div className="flex-1">
                        <Input label="Artist name" value={username} onChange={(e) => setUsername(e.target.value)} />
                    </div>
                    <Button type="submit" icon={Plus} disabled={busy || !username.trim()}>Create</Button>
                </form>
                {message ? (
                    <span className={message.kind === 'ok' ? 'text-sm text-status-success' : 'text-sm text-status-danger'}>
                        {message.text}
                    </span>
                ) : null}
            </section>
        </div>
    );
}

AdminUsersPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
