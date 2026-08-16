import * as React from "react"
import { Switch as SwitchPrimitive } from "@base-ui/react/switch"

import { cn } from "@/lib/utils"

/** Base UI switch, contained-pill style: the knob rides inside the track
 *  (deliberately not the legacy Material thin-track/overhanging-knob look).
 *  Theming is token-driven, so no dark: variants here. */
function Switch({ className, ...props }: SwitchPrimitive.Root.Props) {
  return (
    <SwitchPrimitive.Root
      data-slot="switch"
      className={cn(
        "peer inline-flex h-6 w-10 shrink-0 items-center rounded-pill border border-transparent px-0.5 outline-none transition-[background] duration-(--dur-fast) ease-(--ease-standard) focus-visible:ring-[3px] focus-visible:ring-ring/50 data-disabled:cursor-not-allowed data-disabled:opacity-50 data-checked:bg-purple-600 data-unchecked:bg-border-strong",
        className
      )}
      {...props}
    >
      <SwitchPrimitive.Thumb
        data-slot="switch-thumb"
        className={cn(
          "pointer-events-none block size-[18px] rounded-full bg-(--slate-050) shadow-[0_1px_3px_rgba(0,0,0,0.3)] ring-0 transition-[translate] duration-(--dur-fast) ease-(--ease-standard) data-checked:translate-x-4 data-unchecked:translate-x-0"
        )}
      />
    </SwitchPrimitive.Root>
  )
}

export { Switch }
