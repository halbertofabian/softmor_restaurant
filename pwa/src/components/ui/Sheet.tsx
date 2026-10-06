import { useEffect, type ReactNode } from 'react'
import { createPortal } from 'react-dom'
import { CloseIcon } from './icons'

interface SheetProps {
  open: boolean
  title: string
  onClose: () => void
  children: ReactNode
}

function useLockBodyScroll(open: boolean) {
  useEffect(() => {
    if (!open) {
      return
    }

    const original = document.body.style.overflow
    document.body.style.overflow = 'hidden'

    return () => {
      document.body.style.overflow = original
    }
  }, [open])
}

export function Sheet({ open, title, onClose, children }: SheetProps) {
  useLockBodyScroll(open)

  if (!open) {
    return null
  }

  return createPortal(
    <div
      className="fixed inset-0 z-50 flex items-end justify-center sm:items-center"
      data-pull-to-refresh-ignore
    >
      <button
        type="button"
        aria-label="Cerrar"
        onClick={onClose}
        className="absolute inset-0 bg-black/70 backdrop-blur-sm"
      />

      <div className="relative z-10 max-h-[85vh] w-full max-w-lg overflow-y-auto rounded-t-3xl border border-white/10 bg-card p-5 shadow-2xl sm:rounded-3xl">
        <div className="mb-4 flex items-center justify-between gap-3">
          <h2 className="text-base font-semibold text-white">{title}</h2>
          <button
            type="button"
            onClick={onClose}
            className="rounded-lg p-1.5 text-gray-500 transition hover:bg-white/5 hover:text-white"
          >
            <CloseIcon className="h-4 w-4" />
          </button>
        </div>

        {children}
      </div>
    </div>,
    document.body,
  )
}
