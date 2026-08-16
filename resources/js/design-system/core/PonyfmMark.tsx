import React from 'react';

const WAVE_1 = 'M97.9,62.6c-1.9,0.5-3.8-0.6-4.3-2.5c-2.3-8.5-7.8-15.3-14.8-19.3c-1.7-1-2.2-3.1-1.3-4.8c1-1.6,3-2.2,4.7-1.3h0.1h0.1 c8.5,5,15.2,13.2,18,23.4v0.1v0.1C100.8,60.2,99.7,62.1,97.9,62.6z';
const WAVE_2 = 'M111.4,59c-1.9,0.5-3.8-0.6-4.3-2.5c-3.3-12.2-11.2-22-21.4-27.8c-1.7-1-2.2-3.1-1.3-4.8c1-1.7,3.1-2.2,4.8-1.3 c11.7,6.8,20.8,18,24.6,32.1C114.4,56.5,113.3,58.5,111.4,59z';

/** The Pony.fm disc iconmark, extracted from the wordmark SVG.
 *  Renders in currentColor so it tints like an icon font glyph.
 *
 *  variant="full"    — the complete mark (solid disc, label ring, spindle, waves)
 *  variant="spindle" — only the record centre (spindle dot + label ring) and
 *                      the wave arcs, for overlaying on real artwork. */
export function PonyfmMark({ className, variant = 'full' }: { className?: string; variant?: 'full' | 'spindle' }) {
    if (variant === 'spindle') {
        return (
            <svg viewBox="0 5.5 125.6 125.6" className={className} aria-hidden="true">
                <circle cx="62.8" cy="68.3" r="5.7" fill="currentColor" />
                <circle cx="62.8" cy="68.3" r="24" fill="none" stroke="currentColor" strokeWidth="4.5" />
                <path fill="currentColor" d={WAVE_1} />
                <path fill="currentColor" d={WAVE_2} />
            </svg>
        );
    }
    return (
        <svg viewBox="0 5.5 125.6 125.6" fill="currentColor" className={className} aria-hidden="true">
            <circle cx="62.8" cy="68.3" r="5.7" />
            <path d={'M62.8,5.5C28.1,5.5,0,33.6,0,68.3c0,34.7,28.1,62.8,62.8,62.8c34.7,0,62.8-28.1,62.8-62.8 C125.6,33.6,97.5,5.5,62.8,5.5z M62.8,92.3c-13.3,0-24-10.7-24-24s10.7-24,24-24c13.3,0,24,10.7,24,24S76.1,92.3,62.8,92.3z ' + WAVE_1 + ' ' + WAVE_2} />
        </svg>
    );
}
