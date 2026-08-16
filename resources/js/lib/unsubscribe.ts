const ACTIVITY_STRINGS: Record<number, string> = {
    1: 'updates from the Pony.fm team',
    2: 'new tracks by users you follow',
    3: 'new albums by users you follow',
    4: 'new playlists by users you follow',
    5: 'when you get new followers',
    6: 'when someone leaves you a comment',
    7: 'when something of yours is favourited',
};

/** The confirmation copy for email-unsubscribe landings, ported verbatim. */
export function unsubscribeMessage(activityType: number, displayName?: string | null): string | null {
    const activityString = ACTIVITY_STRINGS[activityType];
    if (!activityString) return null;
    if (displayName) {
        return `${displayName} - you've been unsubscribed from email notifications for ${activityString}. You can re-enable them by logging in and going to your account settings.`;
    }
    return `You successfully unsubscribed from email notifications for ${activityString}. If you want, you can re-subscribe below.`;
}
