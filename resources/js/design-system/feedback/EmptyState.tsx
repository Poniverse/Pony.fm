import React from 'react';
import { Music } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import {
  Empty,
  EmptyContent,
  EmptyDescription,
  EmptyMedia,
  EmptyTitle,
} from '@/design-system/primitives/empty';

/** Dashed purple placeholder for empty listings. */
export interface EmptyStateProps {
  icon?: LucideIcon;
  title?: string;
  action?: React.ReactNode;
  children?: React.ReactNode;
}

export function EmptyState({ icon: Icon = Music, title, children, action }: EmptyStateProps) {
  return (
    <Empty>
      {Icon ? (
        <EmptyMedia>
          <Icon aria-hidden="true" className="size-[26px]" />
        </EmptyMedia>
      ) : null}
      {title ? <EmptyTitle>{title}</EmptyTitle> : null}
      {children ? <EmptyDescription>{children}</EmptyDescription> : null}
      {action ? <EmptyContent>{action}</EmptyContent> : null}
    </Empty>
  );
}
