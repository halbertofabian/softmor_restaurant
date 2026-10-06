import { useEffect, useRef } from 'react'

type OverlayHandler = () => void

let overlayCounter = 0
const overlayStack: number[] = []
const overlayHandlers = new Map<number, OverlayHandler>()

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

export function parentPathOf(pathname: string): string | null {
  if (pathname.startsWith('/orders/')) {
    return '/mesas'
  }

  if (pathname === '/mesas' || pathname === '/historial' || pathname === '/impresoras') {
    return '/'
  }

  return null
}
