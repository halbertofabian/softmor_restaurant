import { useRegisterSW } from 'virtual:pwa-register/react'

export function UpdateBanner() {
  const {
    needRefresh: [needRefresh, setNeedRefresh],
    updateServiceWorker,
  } = useRegisterSW({
    onRegisteredSW(_swUrl, registration) {
      if (!registration) {
        return
      }

      const check = () => {
        void registration.update()
      }

      window.setInterval(check, 60 * 60 * 1000)
      window.addEventListener('focus', check)
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
          check()
        }
      })
    },
  })

  if (!needRefresh) {
    return null
  }

  return (
    <div
      className="fixed inset-x-0 top-3 z-[80] px-3"
      data-pull-to-refresh-ignore
    >
      <div className="mx-auto flex w-full max-w-3xl items-center justify-between gap-3 rounded-2xl border border-primary/30 bg-card/95 p-3 shadow-2xl shadow-black/50 backdrop-blur">
        <p className="min-w-0 text-xs font-medium text-gray-200">
          Hay una nueva versión de GestionalFood disponible.
        </p>

        <div className="flex shrink-0 items-center gap-2">
          <button
            type="button"
            onClick={() => void updateServiceWorker(true)}
            className="rounded-xl bg-primary px-3 py-1.5 text-xs font-bold text-black transition hover:bg-primary-dark"
          >
            Actualizar
          </button>
          <button
            type="button"
            onClick={() => setNeedRefresh(false)}
            className="rounded-xl border border-white/10 px-3 py-1.5 text-xs font-medium text-gray-300 transition hover:bg-white/5"
          >
            Cerrar
          </button>
        </div>
      </div>
    </div>
  )
}
