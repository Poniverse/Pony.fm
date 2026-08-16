import { router } from '@inertiajs/react';
import { isAudioPlaying } from '@/lib/player/PlayerContext';

let listening = false;

/**
 * Signs the user in without interrupting playback: when a track is playing,
 * the OIDC flow runs in a popup that posts back once the session exists and
 * the page reloads its props in place. With nothing playing (or the popup
 * blocked) it is a normal full-page redirect.
 */
export function openLogin() {
    if (!isAudioPlaying()) {
        window.location.href = '/login';
        return;
    }
    const popup = window.open('/login?popup=1', 'pfm-login', 'popup,width=480,height=700');
    if (!popup) {
        window.location.href = '/login';
        return;
    }
    if (!listening) {
        listening = true;
        window.addEventListener('message', (e: MessageEvent) => {
            if (e.origin === window.location.origin && (e.data as { type?: string } | null)?.type === 'pfm:login-complete') {
                router.reload();
            }
        });
    }
}
