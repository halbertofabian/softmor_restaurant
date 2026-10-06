import { useEffect, useRef } from 'react'

type OverlayHandler = () => void

let overlayCounter = 0
const overlayStack: number[] = []
const overlayHandlers = new Map<number, OverlayHandler>()

const BASE_PATH = (import.meta.env.BASE_URL || '/').replace(/\/+$/, '')
const MAIN_VIEWS = new Set(['/mesas', '/impresoras'])

function appPathFromLocation(): string {
  const path = window.location.pathname

  if (BASE_PATH && path.startsWith(BASE_PATH)) {
    return path.slice(BASE_PATH.length) || '/'
  }

  return path
}

let lastKnownPath = appPathFromLocation()
let historyPatched = false

function patchHistory(): void {
  if (historyPatched) {
    return
  }

  historyPatched = true

  const originalPushState = window.history.pushState.bind(window.history)
  const originalReplaceState = window.history.replaceState.bind(window.history)

  window.history.pushState = (data, unused, url) => {
    originalPushState(data, unused, url)
    lastKnownPath = appPathFromLocation()
  }

  window.history.replaceState = (data, unused, url) => {
    originalReplaceState(data, unused, url)
    lastKnownPath = appPathFromLocation()
  }
}

patchHistory()

export function currentAppPath(): string {
  return lastKnownPath
}

export function syncAppPath(): void {
  lastKnownPath = appPathFromLocation()
}

export function isMainView(pathname: string): boolean {
  return MAIN_VIEWS.has(pathname)
}

export function registerOverlayBack(onClose: OverlayHandler): () => void {
  overlayCounter += 1
  const id = overlayCounter

  overlayHandlers.set(id, onClose)
  overlayStack.push(id)

  window.history.pushState({ ...window.history.state, gfOverlay: id }, '')

  return () => {
    overlayHandlers.delete(id)

    const index = overlayStack.indexOf(id)

    if (index !== -1) {
      overlayStack.splice(index, 1)
    }

    if (window.history.state?.gfOverlay === id) {
      window.history.replaceState({ ...window.history.state, gfOverlay: null }, '')
    }
  }
}

export function closeTopOverlay(): boolean {
  const id = overlayStack[overlayStack.length - 1]

  if (id === undefined) {
    return false
  }

  const handler = overlayHandlers.get(id)

  if (!handler) {
    return false
  }

  handler()
  return true
}

export function useOverlayBack(open: boolean, onClose: OverlayHandler): void {
  const closeRef = useRef(onClose)

  useEffect(() => {
    closeRef.current = onClose
  }, [onClose])

  useEffect(() => {
    if (!open) {
      return
    }

    return registerOverlayBack(() => closeRef.current())
  }, [open])
}
