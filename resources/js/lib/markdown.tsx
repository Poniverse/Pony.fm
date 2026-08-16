import React from 'react';
import { marked } from 'marked';
import DOMPurify from 'dompurify';

/**
 * Markdown rendering for descriptions, bios and lyrics. DOMPurify needs a
 * real DOM, so the server (and first client paint) renders the plain text —
 * an effect swaps in the sanitised HTML immediately after hydration.
 */
export function Markdown({ source }: { source: string }) {
    const [html, setHtml] = React.useState<string | null>(null);

    React.useEffect(() => {
        let live = true;
        Promise.resolve(marked.parse(source ?? '')).then((raw) => {
            if (live) setHtml(DOMPurify.sanitize(raw));
        });
        return () => { live = false; };
    }, [source]);

    if (html === null) {
        return <div className="whitespace-pre-wrap">{source}</div>;
    }
    return <div className="markdown" dangerouslySetInnerHTML={{ __html: html }} />;
}
