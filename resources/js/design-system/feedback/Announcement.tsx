import React from 'react';
import { Bell, TriangleAlert, X } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/design-system/primitives/alert';

const ICON: Record<string, LucideIcon> = {
  simple: Bell,
  alert: TriangleAlert,
  serious: TriangleAlert,
};

/** Site-wide announcement banner — events, warnings, outages. */
export interface AnnouncementProps {
  tone?: 'simple' | 'alert' | 'serious';
  title?: string;
  actions?: React.ReactNode;
  onDismiss?: () => void;
  children?: React.ReactNode;
}

export function Announcement({ tone = 'simple', title, children, actions, onDismiss }: AnnouncementProps) {
  const Icon = ICON[tone] || ICON.simple;
  return (
    <Alert variant={ICON[tone] ? tone : 'simple'}>
      <Icon className="pointer-events-none absolute -top-3.5 -left-[18px] size-[104px] -rotate-[9deg] opacity-[0.18]" aria-hidden="true" />
      <div className="relative py-4 pr-[18px] pl-[84px]">
        {title ? <AlertTitle>{title}</AlertTitle> : null}
        <AlertDescription>{children}</AlertDescription>
        {actions ? <div className="mt-3 flex gap-4">{actions}</div> : null}
      </div>
      {onDismiss ? (
        <button type="button" aria-label="Dismiss" onClick={onDismiss}
          className="absolute top-1.5 right-2.5 cursor-pointer border-none bg-transparent p-1.5 text-lg text-[rgba(255,255,255,0.75)]">
          <X className="size-4.5" aria-hidden="true" />
        </button>
      ) : null}
    </Alert>
  );
}
