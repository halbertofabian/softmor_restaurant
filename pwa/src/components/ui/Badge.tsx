import type { ReactNode } from 'react'

type BadgeVariant = 'primary' | 'neutral' | 'success' | 'muted'

const variants: Record<BadgeVariant, string> = {
  primary: 'border-primary/30 bg-primary/10 text-primary',
  neutral: 'border-white/10 bg-white/5 text-gray-300',
  success: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400',
  muted: 'border-white/5 bg-white/5 text-gray-500',
}

export function Badge({
  children,
  variant = 'neutral',
}: {
  children: ReactNode
  variant?: BadgeVariant
}) {
  return (
    <span
      className={`inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-medium ${variants[variant]}`}
    >
      {children}
    </span>
  )
}
