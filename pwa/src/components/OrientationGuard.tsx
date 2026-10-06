import { useEffect, useState } from 'react'
import { DeviceIcon } from './ui/icons'

function isPortraitDevice() {
  return window.matchMedia('(pointer: coarse)').matches
}

function isLandscape() {
  return window.innerWidth > window.innerHeight
}

export function OrientationGuard() {
  const [blocked, setBlocked] = useState(() => isPortraitDevice() && isLandscape())

  useEffect(() => {
    function update() {
      setBlocked(isPortraitDevice() && isLandscape())
    }

    update()
    window.addEventListener('resize', update)
    window.addEventListener('orientationchange', update)

    const media = window.matchMedia('(orientation: landscape)')
    media.addEventListener('change', update)

    return () => {
      window.removeEventListener('resize', update)
      window.removeEventListener('orientationchange', update)
      media.removeEventListener('change', update)
    }
  }, [])

  useEffect(() => {
    const root = document.documentElement
    root.style.overflow = blocked ? 'hidden' : ''

    return () => {
      root.style.overflow = ''
    }
  }, [blocked])

  useEffect(() => {
    const orientation = screen.orientation

    if (typeof orientation?.lock === 'function') {
      orientation.lock('portrait').catch(() => undefined)
    }
  }, [])

  if (!blocked) {
    return null
  }

  return (
    <div
      className="fixed inset-0 z-[90] flex flex-col items-center justify-center gap-4 bg-ink px-8 text-center"
      data-pull-to-refresh-ignore
    >
      <span className="flex h-16 w-16 animate-pulse items-center justify-center rounded-3xl border border-primary/30 bg-primary/10 text-primary">
        <DeviceIcon className="h-8 w-8 rotate-90" />
      </span>
      <h1 className="text-lg font-bold text-white">Gira tu dispositivo</h1>
      <p className="max-w-xs text-sm text-gray-400">
        Esta aplicación solo funciona en vertical. Vuelve a la posición vertical para continuar.
      </p>
    </div>
  )
}
