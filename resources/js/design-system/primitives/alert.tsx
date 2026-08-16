import * as React from "react"
import { cva, type VariantProps } from "class-variance-authority"

import { cn } from "@/lib/utils"

const alertVariants = cva("relative overflow-hidden rounded-card text-white shadow-md", {
  variants: {
    variant: {
      simple: "bg-purple-700",
      alert: "bg-[#a5601a]",
      serious: "bg-[#a83a2c]",
    },
  },
  defaultVariants: {
    variant: "simple",
  },
})

function Alert({
  className,
  variant,
  ...props
}: React.ComponentProps<"div"> & VariantProps<typeof alertVariants>) {
  return (
    <div
      data-slot="alert"
      role="alert"
      className={cn(alertVariants({ variant }), className)}
      {...props}
    />
  )
}

function AlertTitle({ className, ...props }: React.ComponentProps<"h2">) {
  return (
    <h2
      data-slot="alert-title"
      className={cn(
        "mt-0 mb-1 font-display text-xl font-semibold text-white",
        className
      )}
      {...props}
    />
  )
}

function AlertDescription({
  className,
  ...props
}: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="alert-description"
      className={cn("text-sm text-[rgba(255,255,255,0.88)]", className)}
      {...props}
    />
  )
}

export { Alert, AlertTitle, AlertDescription }
