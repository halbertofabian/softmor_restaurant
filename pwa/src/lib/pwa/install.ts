import { useSyncExternalStore } from 'react'

export interface BeforeInstallPromptEvent extends Event {
  prompt: () => Promise<void>
  userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>
}

interface PwaInstallState {
  canInstall: boolean
  installed: boolean
  standalone: boolean
}

type Listener = () => void

const listeners = new Set<Listener>()

let deferredPrompt: BeforeInstallPromptEvent | null = null
let relatedAppInstalled = false
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
  standalone: detectStandalone(),
}

function refreshState() {
  const standalone = detectStandalone()

  state = {
    canInstall: deferredPrompt !== null,
    installed: relatedAppInstalled || standalone,
    standalone,
  }
  listeners.forEach((listener) => listener())
}

async function checkInstalledRelatedApps(): Promise<boolean> {
  const nav = navigator as Navigator & {
    getInstalledRelatedApps?: () => Promise<{ platform?: string }[]>
  }

  if (!nav.getInstalledRelatedApps) {
    return false
  }

  try {
    const apps = await nav.getInstalledRelatedApps()

    return apps.some((app) => app.platform === 'webapp')
  } catch {
    return false
  }
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

export function isInstallSupportedBrowser(): boolean {
  if (typeof navigator === 'undefined') {
    return false
  }

  const ua = navigator.userAgent

  if (/Edg\//.test(ua) || /EdgA\//.test(ua)) {
    return true
  }

  if (/Brave/.test(ua) || /OPR\//.test(ua) || /SamsungBrowser/.test(ua) || /Vivaldi/.test(ua)) {
    return false
  }

  // Chrome en iOS (CriOS) usa WebKit y no permite instalar PWA.
  if (/CriOS\//.test(ua) || /FxiOS\//.test(ua) || /EdgiOS\//.test(ua)) {
    return false
  }

  return /Chrome\//.test(ua)
}

interface BraveNavigator extends Navigator {
  brave?: {
    isBrave?: () => Promise<boolean>
  }
}

export async function isBraveBrowser(): Promise<boolean> {
  if (typeof navigator === 'undefined') {
    return false
  }

  try {
    return (await (navigator as BraveNavigator).brave?.isBrave?.()) === true
  } catch {
    return false
  }
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
    relatedAppInstalled = true
    refreshState()
  })

  window.matchMedia?.('(display-mode: standalone)').addEventListener?.('change', refreshState)
  refreshState()

  void checkInstalledRelatedApps().then((found) => {
    if (found) {
      relatedAppInstalled = true
      refreshState()
    }
  })
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
