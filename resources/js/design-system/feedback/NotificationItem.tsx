import React from 'react';
import { cn } from '@/lib/utils';
import { Avatar } from '../core/Avatar';

/** One row of the notifications popover. */
export interface NotificationItemProps {
  actor: string;
  avatar?: string;
  action?: string;
  target?: string;
  when?: string;
  unread?: boolean;
  onClick?: React.MouseEventHandler;
}

export function NotificationItem({ actor, avatar, action, target, when, unread, onClick }: NotificationItemProps) {
  return (
    <div onClick={onClick}
      className={cn(
        'flex cursor-pointer items-center gap-[11px] rounded-sm border bg-surface-2 px-3 py-2.5 transition-[background,color,border-color] duration-(--dur-fast) ease-(--ease-standard) hover:bg-surface-3',
        unread ? 'border-purple-500' : 'border-border-subtle',
      )}>
      <Avatar src={avatar} name={actor} size="sm" />
      <div className="min-w-0 flex-1 text-sm text-foreground">
        <strong className="font-bold text-heading">{actor}</strong>{' '}{action}{' '}
        {target ? <span className="text-brand-text">{target}</span> : null}
        <div className="mt-0.5 text-2xs text-faint">{when}</div>
      </div>
      {unread ? <span className="size-[7px] flex-none rounded-full bg-purple-400" /> : null}
    </div>
  );
}
