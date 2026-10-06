import { useState, type ReactNode } from 'react'
import { Button } from '../components/ui/Button'
import { CheckIcon, DownloadIcon, ShareIcon } from '../components/ui/icons'
import { usePwaInstall } from '../lib/pwa/install'
import { useToastStore } from '../stores/toastStore'

const SKIP_SESSION_KEY = 'gestionalfood.skip_install_session'

const features = [
  'Toma de comandas desde el celular o tablet',
  'Impresión de tickets por área de preparación',
  'Se abre a pantalla completa, sin barra del navegador',
]

export function InstallGate({ children }: { children: ReactNode }) {
  const { installed, standalone, canInstall, isIos, secureContext, install } = usePwaInstall()
  const pushToast = useToastStore((state) => state.push)
  const [skipped, setSkipped] = useState(() => {
    try {
      return sessionStorage.getItem(SKIP_SESSION_KEY) === '1'
    } catch {
      return false
    }
  })

  async function handleInstall() {
    const outcome = await install()

    if (outcome === 'accepted') {
      pushToast('Instalación iniciada: abre GestionalFood desde tu pantalla de inicio.', 'success')
    } else if (outcome === 'dismissed') {
      pushToast('Instalación cancelada.', 'info')
    } else {
      pushToast('El navegador no ofreció la instalación; usa su menú para agregarla.', 'info')
    }
  }

  function handleSkip() {
    try {
      sessionStorage.setItem(SKIP_SESSION_KEY, '1')
    } catch {
      // Si no se puede persistir, se omite solo en esta sesión.
    }

    setSkipped(true)
  }

  if (standalone || skipped) {
    return <>{children}</>
  }

  if (installed) {
    return (
      <div className="app-min-screen bg-grid relative flex flex-col items-center justify-center overflow-x-hidden bg-ink px-5 py-10">
        <div className="hero-mesh" />

        <div className="relative z-10 w-full max-w-md">
          <div className="flex flex-col items-center text-center">
            <img
              src="/pwa/gestionalfood.png"
              alt="GestionalFood"
              className="h-24 w-24 rounded-3xl shadow-2xl shadow-black/40"
            />
            <h1 className="mt-5 text-2xl font-extrabold tracking-tight text-white">
              GestionalFood
            </h1>
            <p className="mt-1 text-sm text-gray-400">
              Comandas e impresión local por área de preparación
            </p>
          </div>

          <div className="mt-7 flex items-start gap-3 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3">
            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-300">
              <CheckIcon className="h-4 w-4" />
            </span>
            <div>
              <p className="text-sm font-semibold text-emerald-300">Aplicación instalada</p>
              <p className="mt-1 text-xs text-emerald-200/80">
                Ya está instalada en este dispositivo. Ábrela desde el icono de GestionalFood en tu
                pantalla de inicio para usarla a pantalla completa.
              </p>
            </div>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="app-min-screen bg-grid relative flex flex-col items-center justify-center overflow-x-hidden bg-ink px-5 py-10">
      <div className="hero-mesh" />

      <div className="relative z-10 w-full max-w-md">
        <div className="flex flex-col items-center text-center">
          <img
            src="/pwa/gestionalfood.png"
            alt="GestionalFood"
            className="h-24 w-24 rounded-3xl shadow-2xl shadow-black/40"
          />
          <h1 className="mt-5 text-2xl font-extrabold tracking-tight text-white">
            GestionalFood
          </h1>
          <p className="mt-1 text-sm text-gray-400">
            Comandas e impresión local por área de preparación
          </p>
        </div>

        <ul className="mt-6 space-y-2">
          {features.map((feature) => (
            <li key={feature} className="flex items-start gap-2 text-sm text-gray-300">
              <span className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/15 text-primary">
                <CheckIcon className="h-3.5 w-3.5" />
              </span>
              {feature}
            </li>
          ))}
        </ul>

        <div className="mt-7 space-y-3">
          {canInstall ? (
            <>
              <Button className="w-full" size="lg" onClick={handleInstall}>
                <DownloadIcon className="h-4 w-4" />
                Instalar aplicación
              </Button>
              <p className="text-center text-[11px] text-gray-500">
                Se agregará el icono GestionalFood a tu pantalla de inicio.
              </p>
            </>
          ) : !secureContext ? (
            <div className="rounded-2xl border border-primary/25 bg-primary/10 p-4">
              <p className="text-sm font-semibold text-primary">Necesitas HTTPS para instalar</p>
              <p className="mt-1.5 text-xs text-primary/80">
                Estás abriendo la app desde una dirección no segura (HTTP de red local). El
                navegador solo permite instalar PWA desde HTTPS o localhost.
              </p>
              <p className="mt-2 text-[11px] text-primary/70">
                Opciones: publica la app con HTTPS, usa un túnel seguro, o activa en Chrome de
                Android el flag de origen seguro para esta dirección.
              </p>
            </div>
          ) : isIos ? (
            <div className="rounded-2xl border border-white/5 bg-card p-4">
              <p className="mb-2 text-sm font-semibold text-white">
                Para instalarla en iPhone o iPad
              </p>
              <ol className="list-inside list-decimal space-y-1.5 text-xs text-gray-400">
                <li>
                  Toca{' '}
                  <span className="inline-flex items-center gap-1 text-primary">
                    <ShareIcon className="h-3.5 w-3.5" /> Compartir
                  </span>{' '}
                  en Safari.
                </li>
                <li>Elige “Añadir a pantalla de inicio”.</li>
                <li>Confirma con “Agregar”.</li>
              </ol>
            </div>
          ) : (
            <div className="rounded-2xl border border-white/5 bg-card p-4">
              <p className="mb-2 text-sm font-semibold text-white">Para instalarla</p>
              <ol className="list-inside list-decimal space-y-1.5 text-xs text-gray-400">
                <li>Abre el menú del navegador (⋮ o …).</li>
                <li>Elige “Instalar aplicación” o “Agregar a pantalla de inicio”.</li>
                <li>Confirma la instalación.</li>
              </ol>
              <p className="mt-3 text-[11px] text-gray-500">
                La instalación directa requiere HTTPS o localhost.
              </p>
            </div>
          )}

          <button
            type="button"
            onClick={handleSkip}
            className="w-full py-1 text-center text-xs font-medium text-gray-500 transition hover:text-gray-300"
          >
            Continuar en el navegador
          </button>
        </div>
      </div>
    </div>
  )
}
