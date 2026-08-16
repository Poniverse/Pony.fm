import React from 'react';
import { Head, router } from '@inertiajs/react';
import { Bell, Check, Pencil, Plus, Trash2 } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { AdminHeader } from '@/components/AdminHeader';
import { Badge } from '@/design-system/core/Badge';
import { Button } from '@/design-system/core/Button';
import { DateTimePicker } from '@/design-system/core/DatePicker';
import { Input } from '@/design-system/core/Input';
import { Select } from '@/design-system/core/Select';
import { Dialog } from '@/design-system/feedback/Dialog';
import { EmptyState } from '@/design-system/feedback/EmptyState';
import { api } from '@/lib/api';
import { formatDate } from '@/lib/format';

interface AnnouncementRow {
    id: number;
    title: string;
    text_content: string | null;
    announcement_type_id: number;
    start_time: string | null;
    end_time: string | null;
}

const TYPE_OPTIONS = [
    { value: '1', label: 'Generic' },
    { value: '2', label: 'Warning' },
    { value: '3', label: 'Serious' },
];

/** ISO timestamp → value for a datetime-local input, in the viewer's timezone. */
function toLocalInput(iso: string | null): string {
    if (!iso) return '';
    const d = new Date(iso);
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

interface FormState {
    title: string;
    text_content: string;
    announcement_type_id: string;
    start_time: string;
    end_time: string;
}

const EMPTY_FORM: FormState = { title: '', text_content: '', announcement_type_id: '1', start_time: '', end_time: '' };

export default function AdminAnnouncementsPage({ announcements }: { announcements: AnnouncementRow[] }) {
    const [editing, setEditing] = React.useState<AnnouncementRow | 'new' | null>(null);
    const [deleting, setDeleting] = React.useState<AnnouncementRow | null>(null);
    const [form, setForm] = React.useState<FormState>(EMPTY_FORM);
    const [busy, setBusy] = React.useState(false);
    const [error, setError] = React.useState<string | null>(null);
    const now = Date.now();

    const openEditor = (a: AnnouncementRow | 'new') => {
        setForm(a === 'new' ? EMPTY_FORM : {
            title: a.title,
            text_content: a.text_content ?? '',
            announcement_type_id: String(a.announcement_type_id || 1),
            start_time: toLocalInput(a.start_time),
            end_time: toLocalInput(a.end_time),
        });
        setError(null);
        setEditing(a);
    };

    const set = (key: keyof FormState) => (value: string) => setForm((f) => ({ ...f, [key]: value }));

    const canSave = form.title.trim() && form.start_time && form.end_time && !busy;

    const save = () => {
        if (!canSave || editing === null) return;
        setBusy(true);
        const payload = {
            title: form.title.trim(),
            text_content: form.text_content.trim(),
            announcement_type_id: Number(form.announcement_type_id),
            // datetime-local values are in the admin's timezone; store UTC.
            start_time: new Date(form.start_time).toISOString(),
            end_time: new Date(form.end_time).toISOString(),
        };
        const request = editing === 'new'
            ? api.post('/admin/announcements', payload)
            : api.put('/admin/announcements/' + editing.id, payload);
        request
            .then(() => { setEditing(null); router.reload(); })
            .catch((e) => {
                const errors = e.response?.data?.errors as Record<string, string[]> | undefined;
                setError(errors ? Object.values(errors).flat().join(' ') : 'Could not save the announcement.');
            })
            .finally(() => setBusy(false));
    };

    const destroy = () => {
        if (!deleting || busy) return;
        setBusy(true);
        api.delete('/admin/announcements/' + deleting.id)
            .then(() => { setDeleting(null); router.reload(); })
            .catch(() => setError('Could not delete the announcement.'))
            .finally(() => setBusy(false));
    };

    return (
        <div className="grid max-w-[860px] gap-5 px-7 pt-6 pb-12">
            <Head title="Admin - Announcements" />
            <AdminHeader active="announcements" />

            <div className="flex items-center justify-between">
                <p className="m-0 text-sm text-muted-foreground">Announcements appear as a banner across the whole site while they're scheduled.</p>
                <Button icon={Plus} onClick={() => openEditor('new')}>New announcement</Button>
            </div>

            {announcements.length === 0 ? (
                <EmptyState icon={Bell} title="No announcements" />
            ) : (
                <div className="grid gap-0.5">
                    {announcements.map((a) => {
                        const active = a.start_time && a.end_time
                            && new Date(a.start_time).getTime() < now && new Date(a.end_time).getTime() > now;
                        return (
                            <div key={a.id} className="flex items-center gap-2.5 rounded-sm px-2.5 py-[9px] hover:bg-surface-hover">
                                <span className="grid min-w-0 flex-1 gap-0.5">
                                    <span className="truncate text-sm font-semibold text-heading">{a.title}</span>
                                    <span className="text-2xs text-faint">
                                        {a.start_time ? formatDate(a.start_time) + (a.end_time ? ' → ' + formatDate(a.end_time) : '') : 'Not scheduled'}
                                    </span>
                                </span>
                                {active ? <Badge tone="brand" icon={Bell}>Live</Badge> : null}
                                <Button size="sm" variant="secondary" icon={Pencil} onClick={() => openEditor(a)}>Edit</Button>
                                <Button size="sm" variant="ghost" icon={Trash2} aria-label={'Delete ' + a.title} onClick={() => setDeleting(a)} />
                            </div>
                        );
                    })}
                </div>
            )}

            <Dialog
                open={editing !== null}
                title={editing === 'new' ? 'New announcement' : 'Edit announcement'}
                onClose={() => setEditing(null)}
                footer={<>
                    <Button variant="secondary" onClick={() => setEditing(null)}>Cancel</Button>
                    <Button icon={Check} loading={busy} disabled={!canSave} onClick={save}>
                        {editing === 'new' ? 'Create' : 'Save'}
                    </Button>
                </>}
            >
                <div className="grid gap-3.5">
                    <Input label="Title" value={form.title} onChange={(e) => set('title')(e.target.value)} />
                    <Input label="Message" multiline rows={3} value={form.text_content}
                        onChange={(e) => set('text_content')(e.target.value)}
                        hint="Optional detail shown under the title in the banner." />
                    <Select label="Tone" options={TYPE_OPTIONS} value={form.announcement_type_id}
                        onChange={(e) => set('announcement_type_id')(e.target.value)} />
                    <div className="grid grid-cols-2 gap-2.5">
                        <DateTimePicker label="Visible from" value={form.start_time} onChange={set('start_time')} />
                        <DateTimePicker label="Until" value={form.end_time} onChange={set('end_time')} />
                    </div>
                    {error ? <span className="text-sm text-status-danger">{error}</span> : null}
                </div>
            </Dialog>

            <Dialog
                open={deleting !== null}
                title="Delete announcement?"
                onClose={() => setDeleting(null)}
                footer={<>
                    <Button variant="secondary" onClick={() => setDeleting(null)}>Cancel</Button>
                    <Button variant="danger" icon={Trash2} loading={busy} onClick={destroy}>Delete</Button>
                </>}
            >
                “{deleting?.title}” will disappear from the site immediately.
            </Dialog>
        </div>
    );
}

AdminAnnouncementsPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;
