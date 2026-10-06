import type { ReactNode } from 'react'

interface CardProps {
  title?: string
  icon?: ReactNode
  action?: ReactNode
  className?: string
  children: ReactNode
}

export function Card({ title, icon, action, className = '', children }: CardProps) {
  return (
    <section
      className={`relative overflow-hidden rounded-3xl border border-white/5 bg-card p-5 shadow-xl shadow-black/20 ${className}`}
    >
      {(title || action) && (
        <header className="mb-4 flex items-center justify-between gap-3">
          <h2 className="flex items-center gap-2 text-sm font-semibold text-white">
            {icon && <span className="text-primary">{icon}</span>}
            {title}
          </h2>
          {action}
        </header>
      )}

      {children}
    </section>
  )
}
