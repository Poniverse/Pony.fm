import React from 'react';
import { usePage } from '@inertiajs/react';
import { Star } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Button } from '@/design-system/core/Button';
import { IconButton } from '@/design-system/core/IconButton';
import { Popover } from '@/design-system/feedback/Popover';
import type { SharedProps } from '@/lib/types';
import { openLogin } from '@/lib/auth';

/** Star with the fill switched on — favourited state of the same glyph. */
const FilledStar = ((props) => <Star fill="currentColor" {...props} />) as LucideIcon;

/**
 * The page-header Favourite toggle. Signed-out visitors get a prompt to sign
 * in or register instead of a dead button.
 */
export function FavouriteButton({ favourited, onToggle, what = 'music', iconOnly, iconRound, iconSize }: {
    favourited: boolean;
    onToggle: () => void;
    /** Noun for the prompt copy, e.g. "tracks", "albums". */
    what?: string;
    /** Renders as a bare icon button for compact headers. */
    iconOnly?: boolean;
    /** Icon-only styling: round shape and size, e.g. to match a round play button. */
    iconRound?: boolean;
    iconSize?: 'sm' | 'md' | 'lg';
}) {
    const { auth } = usePage<SharedProps>().props;
    const [prompt, setPrompt] = React.useState(false);

    if (auth.user) {
        return iconOnly ? (
            <IconButton icon={favourited ? FilledStar : Star} label={favourited ? 'Favourited' : 'Favourite'}
                round={iconRound} size={iconSize} onClick={onToggle}
                className={favourited
                    ? 'text-favourite [text-shadow:0_0_4px_rgba(0,0,0,0.6)] motion-safe:[&_svg]:animate-[pfm-pop_380ms_var(--ease-out)]'
                    : undefined} />
        ) : (
            <Button variant="secondary" icon={favourited ? FilledStar : Star} onClick={onToggle}>
                {favourited ? 'Favourited' : 'Favourite'}
            </Button>
        );
    }

    return (
        <span className="relative inline-flex">
            {iconOnly ? (
                <IconButton icon={Star} label="Favourite" round={iconRound} size={iconSize} className="opacity-60" onClick={() => setPrompt(true)} />
            ) : (
                <Button variant="secondary" icon={Star} className="opacity-60" onClick={() => setPrompt(true)}>
                    Favourite
                </Button>
            )}
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
