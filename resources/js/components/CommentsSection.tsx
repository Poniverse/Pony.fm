import React from 'react';
import { usePage } from '@inertiajs/react';
import { Comment } from '@/design-system/feedback/Comment';
import { Input } from '@/design-system/core/Input';
import { Button } from '@/design-system/core/Button';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';
import { api } from '@/lib/api';
import { openLogin } from '@/lib/auth';
import { timeAgo } from '@/lib/format';
import type { CommentData, SharedProps } from '@/lib/types';

export type CommentableType = 'track' | 'album' | 'playlist' | 'user';

export function CommentsSection({ type, id, initial = [] }: {
    type: CommentableType;
    id: number;
    initial?: CommentData[];
}) {
    const { auth } = usePage<SharedProps>().props;
    const [comments, setComments] = React.useState<CommentData[]>(initial);
    const [content, setContent] = React.useState('');
    const [busy, setBusy] = React.useState(false);

    React.useEffect(() => setComments(initial), [initial]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const body = content.trim();
        if (!body || busy) return;
        setBusy(true);
        api.post<CommentData>(`/comments/${type}/${id}`, { content: body })
            .then(({ data }) => {
                setComments((list) => [data, ...list]);
                setContent('');
            })
            .finally(() => setBusy(false));
    };

    return (
        <section>
            <SectionHeader title="Comments" count={comments.length} />
            {auth.user ? (
                <form onSubmit={submit} className="mb-3.5 flex items-start gap-2">
                    <div className="flex-1">
                        <Input placeholder="Leave a comment!" value={content} onChange={(e) => setContent(e.target.value)} />
                    </div>
                    <Button type="submit" disabled={busy || !content.trim()}>Post</Button>
                </form>
            ) : (
                <p className="m-0 mb-3.5 text-sm text-muted-foreground">
                    <a href="/login" onClick={(e) => { e.preventDefault(); openLogin(); }}>Log in</a> to leave a comment.
                </p>
            )}
            {comments.length === 0 ? (
                <p className="m-0 text-sm text-faint">No comments yet — be the first!</p>
            ) : comments.map((c) => (
                <Comment key={c.id} author={c.user.name} avatar={c.user.avatars?.small} when={timeAgo(c.created_at)}>
                    {c.content}
                </Comment>
            ))}
        </section>
    );
}
