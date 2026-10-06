import { useSyncExternalStore } from 'react'

export interface BeforeInstallPromptEvent extends Event {
  prompt: () => Promise<void>
  userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>
}

interface PwaInstallState {
  canInstall: boolean
  installed: boolean
}

type Listener = () => void

const listeners = new Set<Listener>()

let deferredPrompt: BeforeInstallPromptEvent | null = null
let initialized = false

function detectStandalone(): boolean {
  if (typeof window === 'undefined') {
    return false
  }

  const standalone = window.matchMedia?.('(display-mode: standalone)').matches ?? false
  const iosStandalone = (navigator as Navigator & { standalone?: boolean }).standalone === true

  return standalone || iosStandalone
}

let state: PwaInstallState = {
  canInstall: false,
  installed: detectStandalone(),
}

function refreshState() {
  state = { canInstall: deferredPrompt !== null, installed: detectStandalone() }
  listeners.forEach((listener) => listener())
}

export function isIosDevice(): boolean {
  if (typeof navigator === 'undefined') {
    return false
  }

  const ua = navigator.userAgent

  return (
    /iphone|ipad|ipod/i.test(ua) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)
  )
}

export function initPwaInstall(): void {
  if (initialized || typeof window === 'undefined') {
    return
  }

  initialized = true

  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault()
    deferredPrompt = event as BeforeInstallPromptEvent
    refreshState()
  })

  window.addEventListener('appinstalled', () => {
    deferredPrompt = null
    refreshState()
  })

  window.matchMedia?.('(display-mode: standalone)').addEventListener?.('change', refreshState)
  refreshState()
}

export async function promptInstall(): Promise<'accepted' | 'dismissed' | 'unavailable'> {
  if (!deferredPrompt) {
    return 'unavailable'
  }

  const event = deferredPrompt

  await event.prompt()
  const choice = await event.userChoice

  if (choice.outcome === 'accepted') {
    deferredPrompt = null
    refreshState()
  }

  return choice.outcome
}

function subscribe(listener: Listener): () => void {
  listeners.add(listener)

  return () => listeners.delete(listener)
}

function getSnapshot(): PwaInstallState {
  return state
}

export function usePwaInstall() {
  const installState = useSyncExternalStore(subscribe, getSnapshot, getSnapshot)

  return {
    ...installState,
    isIos: isIosDevice(),
    secureContext: typeof window !== 'undefined' && window.isSecureContext,
    install: promptInstall,
  }
}
