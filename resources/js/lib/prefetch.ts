/**
 * Prefetches every built chunk after the page has loaded, so later
 * navigations hit the HTTP cache instead of the network. Chunks are
 * content-hashed (and served immutable), so this never fetches stale code.
 * Best-effort: skipped in dev, on data-saver, and on 2G-class connections.
 */

interface ManifestChunk {
    file: string;
    css?: string[];
}

function inject(urls: string[]) {
    const have = new Set(
        Array.from(document.querySelectorAll<HTMLScriptElement | HTMLLinkElement>('script[src], link[href]'))
            .map((el) => (el instanceof HTMLScriptElement ? el.src : el.href)),
    );
    for (const url of urls) {
        if (have.has(new URL(url, window.location.origin).href)) continue;
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.as = url.endsWith('.css') ? 'style' : 'script';
        link.href = url;
        document.head.appendChild(link);
    }
}

async function run() {
    try {
        const res = await fetch('/assets/manifest.json');
        if (!res.ok) return;
        const manifest: Record<string, ManifestChunk> = await res.json();
        const urls = new Set<string>();
        for (const chunk of Object.values(manifest)) {
            if (chunk.file && (chunk.file.endsWith('.js') || chunk.file.endsWith('.css'))) {
                urls.add('/assets/' + chunk.file);
            }
            for (const css of chunk.css ?? []) {
                urls.add('/assets/' + css);
            }
        }
        inject(Array.from(urls));
    } catch {
        // Prefetching is an optimisation; never let it break the page.
    }
}

export function prefetchAllAssets() {
    if (import.meta.env.DEV || typeof window === 'undefined') return;

    type NetInfo = { saveData?: boolean; effectiveType?: string };
    const connection = (navigator as Navigator & { connection?: NetInfo }).connection;
    if (connection?.saveData || /(^|-)2g$/.test(connection?.effectiveType ?? '')) return;

    const idle = () => {
        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(() => void run(), { timeout: 5000 });
        } else {
            setTimeout(() => void run(), 2000);
        }
    };

    if (document.readyState === 'complete') {
        idle();
    } else {
        window.addEventListener('load', idle, { once: true });
    }
}
