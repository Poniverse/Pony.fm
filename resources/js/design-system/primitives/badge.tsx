import * as React from "react"
import { mergeProps } from "@base-ui/react/merge-props"
import { useRender } from "@base-ui/react/use-render"
import { cva, type VariantProps } from "class-variance-authority"

import { cn } from "@/lib/utils"

const badgeVariants = cva(
  "inline-flex items-center gap-[5px] whitespace-nowrap rounded-xs px-2 py-[3px] font-text text-2xs font-bold",
  {
    variants: {
      tone: {
        neutral: "bg-surface-3 text-muted-foreground",
        brand: "bg-brand-quiet text-brand-text",
        lossless: "bg-[rgba(127,179,168,0.16)] text-status-lossless",
        vocal: "bg-[rgba(194,136,156,0.16)] text-pink-300",
        warning: "bg-[rgba(217,154,43,0.16)] text-status-warning",
        danger: "bg-[rgba(207,90,82,0.16)] text-status-danger",
      },
      uppercase: {
        true: "uppercase tracking-caps",
        false: "tracking-wide",
      },
    },
    defaultVariants: {
      tone: "neutral",
      uppercase: true,
    },
  }
)

function Badge({
  className,
  tone,
  uppercase,
  render,
  ...props
}: useRender.ComponentProps<"span"> & VariantProps<typeof badgeVariants>) {
  return useRender({
    defaultTagName: "span",
    render,
    props: mergeProps<"span">(
      {
        "data-slot": "badge",
        className: cn(badgeVariants({ tone, uppercase }), className),
      } as React.ComponentProps<"span">,
      props
    ),
  })
}

export { Badge, badgeVariants }
