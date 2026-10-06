import { useEffect, type ReactNode } from 'react'
import { createPortal } from 'react-dom'
import { AlertIcon } from './icons'

interface ConfirmDialogProps {
  open: boolean
  title: string
  message: ReactNode
  confirmLabel?: string
  cancelLabel?: string
  loading?: boolean
  onConfirm: () => void
  onCancel: () => void
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

export function ConfirmDialog({
  open,
  title,
  message,
  confirmLabel = 'Confirmar',
  cancelLabel = 'Cancelar',
  loading = false,
  onConfirm,
  onCancel,
}: ConfirmDialogProps) {
  useLockBodyScroll(open)

  if (!open) {
    return null
  }

  return createPortal(
    <div className="fixed inset-0 z-50 flex items-center justify-center px-4">
      <button
        type="button"
        aria-label="Cerrar"
        onClick={onCancel}
        className="absolute inset-0 bg-black/70 backdrop-blur-sm"
      />

      <div className="relative z-10 w-full max-w-sm rounded-3xl border border-white/10 bg-card p-5 shadow-2xl">
        <div className="flex items-start gap-3">
          <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
            <AlertIcon className="h-5 w-5" />
          </span>
          <div>
            <h2 className="text-base font-semibold text-white">{title}</h2>
            <div className="mt-1 text-sm text-gray-400">{message}</div>
          </div>
        </div>

        <div className="mt-5 flex justify-end gap-2">
          <button
            type="button"
            onClick={onCancel}
            disabled={loading}
            className="whitespace-nowrap rounded-xl border border-white/10 px-4 py-2 text-sm font-medium text-gray-300 transition hover:bg-white/5 disabled:opacity-50"
          >
            {cancelLabel}
          </button>
          <button
            type="button"
            onClick={onConfirm}
            disabled={loading}
            className="whitespace-nowrap rounded-xl bg-primary px-4 py-2 text-sm font-bold text-black shadow-lg shadow-primary/20 transition hover:bg-primary-dark disabled:opacity-50"
          >
            {loading ? 'Enviando…' : confirmLabel}
          </button>
        </div>
      </div>
    </div>,
    document.body,
  )
}
