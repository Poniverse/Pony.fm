import React from 'react';
import { usePage } from '@inertiajs/react';
import { Check, Plus } from 'lucide-react';
import { Button } from '@/design-system/core/Button';
import { api } from '@/lib/api';
import { openLogin } from '@/lib/auth';
import type { SharedProps } from '@/lib/types';

export function FollowButton({ artistId, initialFollowing }: { artistId: number; initialFollowing: boolean }) {
    const { auth } = usePage<SharedProps>().props;
    const [following, setFollowing] = React.useState(initialFollowing);

    React.useEffect(() => setFollowing(initialFollowing), [artistId, initialFollowing]);

    if (auth.user?.id === artistId) return null;

    const toggle = () => {
        if (!auth.user) {
            openLogin();
            return;
        }
        setFollowing((f) => !f);
        api.post<{ is_followed: boolean }>('/follow/toggle', { type: 'artist', id: artistId })
            .then(({ data }) => setFollowing(data.is_followed))
            .catch(() => setFollowing((f) => !f));
    };

    return (
        <Button variant={following ? 'quiet' : 'primary'} icon={following ? Check : Plus} onClick={toggle}>
            {following ? 'Following' : 'Follow'}
        </Button>
    );
}
