import { useEffect, useState } from 'react';

/** SSR-safe media query — false on the server and first paint. */
export function useMediaQuery(query: string): boolean {
    const [matches, setMatches] = useState(false);

    useEffect(() => {
        const mq = window.matchMedia(query);
        setMatches(mq.matches);
        const onChange = (e: MediaQueryListEvent) => setMatches(e.matches);
        mq.addEventListener('change', onChange);
        return () => mq.removeEventListener('change', onChange);
    }, [query]);

    return matches;
}

export const MOBILE_QUERY = '(max-width: 768px)';
