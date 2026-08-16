import React from 'react';

/** Display-face section heading with the thin rule underneath. */
export interface SectionHeaderProps {
  title: React.ReactNode;
  count?: number;
  action?: React.ReactNode;
  level?: 1 | 2 | 3 | 4;
}

export function SectionHeader({ title, count, action, level = 2 }: SectionHeaderProps) {
  const Tag = ('h' + level) as 'h1' | 'h2' | 'h3' | 'h4';
  return (
    <div className="mb-1.5 flex items-baseline gap-3 border-b border-border-subtle pb-2">
      <Tag className="m-0 font-display text-xl font-semibold tracking-display text-heading">{title}</Tag>
      {count != null ? <span className="font-mono text-xs text-faint">{count}</span> : null}
      {action ? <span className="ml-auto">{action}</span> : null}
    </div>
  );
}
