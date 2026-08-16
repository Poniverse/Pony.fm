import React from 'react';
import { Head, router } from '@inertiajs/react';
import { Check } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { AccountHeader } from '@/components/AccountHeader';
import { ImageUpload } from '@/components/ImageUpload';
import { Button } from '@/design-system/core/Button';
import { Input } from '@/design-system/core/Input';
import { Switch } from '@/design-system/core/Switch';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';
import { api } from '@/lib/api';

interface NotificationSetting {
    description: string;
    activity_type: number;
    receive_emails: boolean;
}

interface Settings {
    id: number;
    bio: string;
    can_see_explicit_content: boolean;
    display_name: string;
    slug: string;
    username: string;
    gravatar: string;
    avatar_url: string | null;
    uses_gravatar: boolean;
    notification_email: string;
    notifications: NotificationSetting[];
}

export default function SettingsPage({ accountSlug, settings }: { accountSlug: string; settings: Settings }) {
    const [form, setForm] = React.useState({
        display_name: settings.display_name,
        slug: settings.slug,
        bio: settings.bio ?? '',
        can_see_explicit_content: settings.can_see_explicit_content,
        uses_gravatar: settings.uses_gravatar,
        gravatar: settings.gravatar ?? '',
    });
    const [avatar, setAvatar] = React.useState<{ type: 'file'; file: File } | { type: 'gallery'; imageId: number } | null>(null);
    const [notifications, setNotifications] = React.useState(settings.notifications);
    const [errors, setErrors] = React.useState<Record<string, string[]>>({});
    const [busy, setBusy] = React.useState(false);
    const [saved, setSaved] = React.useState(false);

    const save = () => {
        if (busy) return;
        setBusy(true);
        setSaved(false);
        const data = new FormData();
        data.append('display_name', form.display_name);
        data.append('slug', form.slug);
        data.append('bio', form.bio);
        data.append('can_see_explicit_content', form.can_see_explicit_content ? 'true' : 'false');
        data.append('uses_gravatar', form.uses_gravatar ? 'true' : 'false');
        if (form.uses_gravatar) {
            data.append('gravatar', form.gravatar);
        } else if (avatar?.type === 'file') {
            data.append('avatar', avatar.file);
        } else if (avatar?.type === 'gallery') {
            data.append('avatar_id', String(avatar.imageId));
        }
        data.append('notifications', JSON.stringify(notifications));

        api.post('/account/settings/save/' + accountSlug, data)
            .then(() => {
                setErrors({});
                setSaved(true);
                if (form.slug !== accountSlug) {
                    router.visit('/' + form.slug + '/account');
                } else {
                    router.reload();
                }
            })
            .catch((e) => setErrors(e.response?.data?.errors ?? {}))
            .finally(() => setBusy(false));
    };

    return (
        <div className="grid max-w-[760px] gap-6 px-7 pt-6 pb-12">
            <Head title="Account settings" />
            <AccountHeader slug={accountSlug} active="settings" />

            <section className="grid gap-3.5">
                <SectionHeader title="Profile" level={2} />
                <Input label="Display name" value={form.display_name} error={errors.display_name?.[0]}
                    onChange={(e) => setForm({ ...form, display_name: e.target.value })} />
                <Input label="Profile URL" value={form.slug} error={errors.slug?.[0]}
                    hint={'Your profile will live at pony.fm/' + (form.slug || '…')}
                    onChange={(e) => setForm({ ...form, slug: e.target.value })} />
                <Input label="Bio" multiline rows={5} value={form.bio} error={errors.bio?.[0]}
                    hint="Markdown is fine here."
                    onChange={(e) => setForm({ ...form, bio: e.target.value })} />
                <Switch label="Show explicit content" checked={form.can_see_explicit_content}
                    onChange={(v) => setForm({ ...form, can_see_explicit_content: v })} />
            </section>

            <section className="grid gap-3.5">
                <SectionHeader title="Avatar" level={2} />
                <Switch label="Use Gravatar" checked={form.uses_gravatar}
                    onChange={(v) => setForm({ ...form, uses_gravatar: v })} />
                {form.uses_gravatar ? (
                    <Input label="Gravatar email" type="email" value={form.gravatar} error={errors.gravatar?.[0]}
                        onChange={(e) => setForm({ ...form, gravatar: e.target.value })} />
                ) : (
                    <ImageUpload label="Avatar (PNG, at least 350×350)" currentUrl={settings.avatar_url} onChange={setAvatar} />
                )}
                {errors.avatar?.[0] ? <span className="text-2xs text-status-danger">{errors.avatar[0]}</span> : null}
            </section>

            <section className="grid gap-2.5">
                <SectionHeader title="Email notifications" level={2} />
                <p className="m-0 text-xs text-faint">
                    Sent to {settings.notification_email}
                </p>
                <div className="grid gap-2">
                    {notifications.map((n) => (
                        <Switch key={n.activity_type} label={n.description} checked={n.receive_emails}
                            onChange={(v) => setNotifications((list) => list.map((x) => x.activity_type === n.activity_type ? { ...x, receive_emails: v } : x))} />
                    ))}
                </div>
            </section>

            <div className="flex items-center gap-2.5">
                <Button size="lg" icon={Check} disabled={busy} onClick={save}>{busy ? 'Saving…' : 'Save settings'}</Button>
                {saved ? <span className="text-sm text-status-success">Saved!</span> : null}
                {Object.keys(errors).length ? <span className="text-sm text-status-danger">Some fields need attention.</span> : null}
            </div>
        </div>
    );
}

SettingsPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
