import React from 'react';
import { Avatar } from '../core/Avatar';

/** One comment row — avatar, author, time, body. */
export interface CommentProps {
  author: string;
  avatar?: string;
  when?: string;
  onReply?: () => void;
  children?: React.ReactNode;
}

export function Comment({ author, avatar, when, children, onReply }: CommentProps) {
  return (
    <div className="flex gap-[11px] border-b border-border-subtle px-0 py-3">
      <Avatar src={avatar} name={author} size="sm" />
      <div className="min-w-0 flex-1">
        <div className="flex items-baseline gap-2">
          <span className="text-sm font-bold text-heading">{author}</span>
          <span className="text-2xs text-faint">{when}</span>
        </div>
        <div className="mt-[3px] text-sm text-foreground">{children}</div>
        {onReply ? <button type="button" onClick={onReply} className="mt-1.5 cursor-pointer border-none bg-transparent p-0 font-text text-xs text-link">Reply</button> : null}
      </div>
    </div>
  );
}
