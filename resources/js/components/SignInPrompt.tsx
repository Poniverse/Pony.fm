import React from 'react';
import { Button } from '@/design-system/core/Button';
import { Dialog } from '@/design-system/feedback/Dialog';
import { onSignInPrompt } from '@/lib/events';
import { openLogin } from '@/lib/auth';

/**
 * Site-wide "sign in or register" dialog, opened via emitSignInPrompt() from
 * any control that needs an account (favouriting, etc.). Mounted once in the
 * layout.
 */
export function SignInPrompt() {
    const [what, setWhat] = React.useState<string | null>(null);
    React.useEffect(() => onSignInPrompt(setWhat), []);

    return (
        <Dialog open={what !== null} title="Sign in to favourite" onClose={() => setWhat(null)} width={380}
            footer={<>
                <Button render={<a href="/register" />} variant="secondary">Register</Button>
                <Button onClick={() => { setWhat(null); openLogin(); }}>Sign in</Button>
            </>}>
            Favourites live on your Pony.fm account, so you can find the {what} you love again later.
        </Dialog>
    );
}
