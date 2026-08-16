/** Tiny cross-component signals (e.g. sidebar refreshes when playlists change). */

const PLAYLISTS_CHANGED = 'pfm:playlists-changed';

export function emitPlaylistsChanged() {
    window.dispatchEvent(new Event(PLAYLISTS_CHANGED));
}

export function onPlaylistsChanged(handler: () => void): () => void {
    window.addEventListener(PLAYLISTS_CHANGED, handler);
    return () => window.removeEventListener(PLAYLISTS_CHANGED, handler);
}

const SIGN_IN_PROMPT = 'pfm:sign-in-prompt';

/** Asks the layout to show the "sign in or register" dialog.
 *  `what` is the noun for the copy, e.g. "tracks". */
export function emitSignInPrompt(what: string) {
    window.dispatchEvent(new CustomEvent(SIGN_IN_PROMPT, { detail: what }));
}

export function onSignInPrompt(handler: (what: string) => void): () => void {
    const listener = (e: Event) => handler((e as CustomEvent<string>).detail);
    window.addEventListener(SIGN_IN_PROMPT, listener);
    return () => window.removeEventListener(SIGN_IN_PROMPT, listener);
}
