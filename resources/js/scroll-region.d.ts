import 'react';

declare module 'react' {
    interface HTMLAttributes<T> {
        /**
         * Inertia scroll-region attribute: elements carrying it have their
         * scroll position saved to history state and restored on back/forward.
         */
        'scroll-region'?: '';
    }
}
