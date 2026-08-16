import React from 'react';
import { usePage } from '@inertiajs/react';
import { Star } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Button } from '@/design-system/core/Button';
import { Popover } from '@/design-system/feedback/Popover';
import type { SharedProps } from '@/lib/types';
import { openLogin } from '@/lib/auth';

/** Star with the fill switched on — favourited state of the same glyph. */
const FilledStar = ((props) => <Star fill="currentColor" {...props} />) as LucideIcon;

/**
 * The page-header Favourite toggle. Signed-out visitors get a prompt to sign
 * in or register instead of a dead button.
 */
export function FavouriteButton({ favourited, onToggle, what = 'music' }: {
    favourited: boolean;
    onToggle: () => void;
    /** Noun for the prompt copy, e.g. "tracks", "albums". */
    what?: string;
}) {
    const { auth } = usePage<SharedProps>().props;
    const [prompt, setPrompt] = React.useState(false);

    if (auth.user) {
        return (
            <Button variant="secondary" icon={favourited ? FilledStar : Star} onClick={onToggle}>
                {favourited ? 'Favourited' : 'Favourite'}
            </Button>
        );
    }

    return (
        <span className="relative inline-flex">
            <Button variant="secondary" icon={Star} className="opacity-60" onClick={() => setPrompt(true)}>
                Favourite
            </Button>
            <Popover open={prompt} onClose={() => setPrompt(false)} placement="below" width={280} title="Sign in to favourite">
                <div className="grid gap-3">
                    <p className="m-0 text-sm leading-snug text-muted-foreground">
                        Favourites live on your Pony.fm account, so you can find the {what} you love again later.
                    </p>
                    <span className="flex gap-2">
                        <Button size="sm" onClick={() => { setPrompt(false); openLogin(); }}>Sign in</Button>
                        <Button render={<a href="/register" />} size="sm" variant="secondary">Register</Button>
                    </span>
                </div>
            </Popover>
        </span>
    );
}
