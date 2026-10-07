import { useToastStore, type ToastTone } from '../../stores/toastStore'
import { AlertIcon, CheckIcon } from './icons'

const tones: Record<ToastTone, string> = {
  success: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
  error: 'border-red-500/30 bg-red-500/10 text-red-300',
  info: 'border-white/10 bg-card text-gray-200',
}

export function Toaster() {
  const toasts = useToastStore((state) => state.toasts)
  const dismiss = useToastStore((state) => state.dismiss)

  if (toasts.length === 0) {
    return null
  }

  return (
    <div className="pointer-events-none fixed inset-x-0 top-3 z-[70] mx-auto flex w-full max-w-md flex-col gap-2 px-4">
      {toasts.map((toast) => (
        <button
          key={toast.id}
          type="button"
          onClick={() => dismiss(toast.id)}
          className={`pointer-events-auto flex items-start gap-2 rounded-2xl border px-4 py-3 text-left text-sm shadow-xl shadow-black/30 backdrop-blur ${tones[toast.tone]}`}
        >
          {toast.tone === 'success' ? (
            <CheckIcon className="mt-0.5 h-4 w-4 shrink-0" />
          ) : (
            <AlertIcon className="mt-0.5 h-4 w-4 shrink-0" />
          )}
          <span className="min-w-0 break-words">{toast.message}</span>
        </button>
      ))}
    </div>
  )
}
