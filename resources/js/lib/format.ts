/** "247.32" (seconds) → "4:07"; hour-long tracks get "1:02:33". */
export function formatDuration(seconds: number | string | null | undefined): string {
    const total = Math.max(0, Math.floor(Number(seconds) || 0));
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    const s = total % 60;
    const mm = h > 0 ? String(m).padStart(2, '0') : String(m);
    return (h > 0 ? h + ':' + mm : mm) + ':' + String(s).padStart(2, '0');
}

/** Mapper dates arrive either as ISO strings or Carbon's {date: "..."} shape. */
export function toDate(value: string | { date: string } | null | undefined): Date | null {
    if (!value) return null;
    const raw = typeof value === 'string' ? value : value.date;
    const d = new Date(raw.replace(' ', 'T'));
    return isNaN(d.getTime()) ? null : d;
}

const UNITS: [Intl.RelativeTimeFormatUnit, number][] = [
    ['year', 31536000],
    ['month', 2592000],
    ['week', 604800],
    ['day', 86400],
    ['hour', 3600],
    ['minute', 60],
];

/** "3 days ago" — replaces the old jquery.timeago / momentFromNow filters. */
export function timeAgo(value: string | { date: string } | Date | null | undefined): string {
    const d = value instanceof Date ? value : toDate(value);
    if (!d) return '';
    const seconds = Math.round((Date.now() - d.getTime()) / 1000);
    const rtf = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });
    for (const [unit, size] of UNITS) {
        if (Math.abs(seconds) >= size) {
            return rtf.format(-Math.round(seconds / size), unit);
        }
    }
    return rtf.format(-seconds, 'second');
}

/** Long-form date for stat lists: "14 August 2026". */
export function formatDate(value: string | { date: string } | null | undefined): string {
    const d = toDate(value);
    return d ? d.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) : '';
}
