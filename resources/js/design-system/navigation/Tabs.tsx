import React from 'react';
import type { LucideIcon } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { Tabs as UiTabs, TabsList, TabsTrigger } from '@/design-system/primitives/tabs';

/** Underlined tab strip with the site's pink underline. Base UI-backed
 *  (roving tabindex, arrow-key navigation, aria). Used as navigation —
 *  no TabsContent panels. Tabs with an href render as real links
 *  (middle-click / cmd-click friendly); otherwise callers route on change. */
export interface Tab { id: string; label: string; icon?: LucideIcon; count?: number; href?: string }
export interface TabsProps { tabs: Tab[]; active?: string; onChange?: (id: string) => void }

const triggerClass = cn(
    'inline-flex flex-none grow-0 items-center gap-[7px] rounded-none border-x-0 border-t-0 border-b-2 bg-transparent px-3.5 py-[9px] font-text text-sm shadow-none transition-[background,color,border-color] duration-(--dur-fast) ease-(--ease-standard)',
    '-mb-0.5 border-transparent font-normal text-muted-foreground no-underline',
    'data-active:border-(--pfm-pink-deep) data-active:bg-transparent data-active:font-semibold data-active:text-heading data-active:shadow-none',
);

export function Tabs({ tabs = [], active, onChange }: TabsProps) {
    return (
        <UiTabs value={active ?? ''} onValueChange={(id) => onChange?.(id)}>
            <TabsList className="flex h-auto w-full justify-start gap-0.5 rounded-none border-b-2 border-border-subtle bg-transparent p-0">
                {tabs.map((t) => {
                    const inner = (
                        <>
                            {t.icon ? <t.icon aria-hidden="true" className="size-[1.15em]" /> : null}
                            {t.label}
                            {t.count != null ? <span className="font-mono text-2xs text-faint">{t.count}</span> : null}
                        </>
                    );
                    return t.href ? (
                        <TabsTrigger key={t.id} value={t.id} className={triggerClass} render={<Link href={t.href} />} nativeButton={false}>
                            {inner}
                        </TabsTrigger>
                    ) : (
                        <TabsTrigger key={t.id} value={t.id} className={triggerClass}>
                            {inner}
                        </TabsTrigger>
                    );
                })}
            </TabsList>
        </UiTabs>
    );
}
