import React from 'react';
import { router } from '@inertiajs/react';
import { Check, Pencil, Plus, Tags, Trash2 } from 'lucide-react';
import { Button } from '@/design-system/core/Button';
import { IconButton } from '@/design-system/core/IconButton';
import { Input } from '@/design-system/core/Input';
import { Select } from '@/design-system/core/Select';
import { Dialog } from '@/design-system/feedback/Dialog';
import { EmptyState } from '@/design-system/feedback/EmptyState';
import { api } from '@/lib/api';

export interface TaxonomyItem {
    id: number;
    name: string;
    track_count: number;
}

/**
 * Shared admin list for genres and show songs: inline rename, create,
 * and delete-with-merge (tracks move to a destination before deletion).
 */
export function TaxonomyManager({ items, endpoint, nameField, destinationField, noun }: {
    items: TaxonomyItem[];
    /** '/admin/genres' or '/admin/showsongs' */
    endpoint: string;
    /** Field name the create/rename endpoints expect, e.g. 'name' or 'title' */
    nameField: string;
    /** Query param for delete-with-merge, e.g. 'destination_genre_id' */
    destinationField: string;
    noun: string;
}) {
    const [newName, setNewName] = React.useState('');
    const [renaming, setRenaming] = React.useState<Record<number, string>>({});
    const [deleting, setDeleting] = React.useState<TaxonomyItem | null>(null);
    const [destination, setDestination] = React.useState('');
    const [busy, setBusy] = React.useState(false);
    const [error, setError] = React.useState<string | null>(null);

    const finish = () => {
        setBusy(false);
        setError(null);
        router.reload();
    };

    const fail = (e: { response?: { data?: { errors?: Record<string, string[]>; message?: string } } }) => {
        setBusy(false);
        const errors = e.response?.data?.errors;
        setError(errors ? Object.values(errors).flat().join(' ') : (e.response?.data?.message ?? 'Something went wrong.'));
    };

    const create = () => {
        if (!newName.trim() || busy) return;
        setBusy(true);
        api.post(endpoint, { [nameField]: newName.trim() })
            .then(() => { setNewName(''); finish(); })
            .catch(fail);
    };

    const rename = (item: TaxonomyItem) => {
        const name = renaming[item.id]?.trim();
        if (!name || name === item.name || busy) {
            setRenaming(({ [item.id]: _, ...rest }) => rest);
            return;
        }
        setBusy(true);
        api.put(endpoint + '/' + item.id, { [nameField]: name })
            .then(() => { setRenaming(({ [item.id]: _, ...rest }) => rest); finish(); })
            .catch(fail);
    };

    const destroy = () => {
        if (!deleting || !destination || busy) return;
        setBusy(true);
        api.delete(endpoint + '/' + deleting.id, { params: { [destinationField]: destination } })
            .then(() => { setDeleting(null); setDestination(''); finish(); })
            .catch(fail);
    };

    return (
        <div className="grid gap-3.5">
            <form onSubmit={(e) => { e.preventDefault(); create(); }} className="flex max-w-[420px] items-end gap-2">
                <div className="flex-1">
                    <Input label={'New ' + noun} value={newName} onChange={(e) => setNewName(e.target.value)} />
                </div>
                <Button type="submit" icon={Plus} disabled={busy || !newName.trim()}>Add</Button>
            </form>
            {error ? <span className="text-sm text-status-danger">{error}</span> : null}

            {items.length === 0 ? (
                <EmptyState icon={Tags} title={'No ' + noun + 's yet'} />
            ) : (
                <div className="grid gap-0.5">
                    {items.map((item) => (
                        <div key={item.id} className="flex items-center gap-2.5 rounded-sm px-2.5 py-[7px]">
                            {renaming[item.id] !== undefined ? (
                                <form onSubmit={(e) => { e.preventDefault(); rename(item); }} className="flex flex-1 gap-2">
                                    <div className="flex-1">
                                        <Input value={renaming[item.id]} onChange={(e) => setRenaming((r) => ({ ...r, [item.id]: e.target.value }))} />
                                    </div>
                                    <Button size="sm" type="submit" icon={Check} disabled={busy}>Rename</Button>
                                </form>
                            ) : (
                                <span className="min-w-0 flex-1 truncate text-sm text-heading">{item.name}</span>
                            )}
                            <span className="font-mono text-xs text-faint">{item.track_count} tracks</span>
                            <IconButton icon={Pencil} label={'Rename ' + item.name} size="sm"
                                onClick={() => setRenaming((r) => ({ ...r, [item.id]: item.name }))} />
                            <IconButton icon={Trash2} label={'Delete ' + item.name} size="sm"
                                onClick={() => { setDeleting(item); setDestination(''); }} />
                        </div>
                    ))}
                </div>
            )}

            <Dialog open={deleting != null} title={'Delete "' + (deleting?.name ?? '') + '"?'} onClose={() => setDeleting(null)}
                footer={<>
                    <Button variant="secondary" onClick={() => setDeleting(null)}>Cancel</Button>
                    <Button variant="danger" icon={Trash2} disabled={!destination || busy} onClick={destroy}>Delete and move tracks</Button>
                </>}>
                <div className="grid gap-3">
                    <p className="m-0">
                        Its {deleting?.track_count ?? 0} tracks need somewhere to go first.
                    </p>
                    <Select label={'Move tracks to'} value={destination}
                        options={[{ value: '', label: 'Pick a ' + noun + '…' },
                            ...items.filter((i) => i.id !== deleting?.id).map((i) => ({ value: String(i.id), label: i.name }))]}
                        onChange={(e) => setDestination(e.target.value)} />
                </div>
            </Dialog>
        </div>
    );
}
