import { useState } from 'react';

export type Theme = 'dark' | 'light';

/**
 * Theme state. The initial value is stamped on <html data-theme> by the
 * inline bootstrap script in app.blade.php (localStorage, falling back to
 * prefers-color-scheme, falling back to dark) before hydration.
 */
export function useTheme() {
    const [theme, setThemeState] = useState<Theme>(() =>
        typeof document === 'undefined'
            ? 'dark'
            : (document.documentElement.dataset.theme as Theme) || 'dark',
    );

    const setTheme = (t: Theme) => {
        setThemeState(t);
        document.documentElement.dataset.theme = t;
        try {
            localStorage.setItem('pfm-theme', t);
        } catch {
            // private browsing — the choice just won't persist
        }
    };

    return { theme, setTheme, toggle: () => setTheme(theme === 'dark' ? 'light' : 'dark') };
}
