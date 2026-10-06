const DESKTOP_MODE_MIN_WIDTH = 600
const SCREEN_RATIO = 1.25

function getDeviceWidth(): number {
  const sizes = [window.screen?.width ?? 0, window.screen?.height ?? 0].filter(
    (value) => value > 0,
  )

  return sizes.length > 0 ? Math.min(...sizes) : 0
}

function shouldLockViewport(deviceWidth: number): boolean {
  if (navigator.maxTouchPoints <= 0 || deviceWidth <= 0 || deviceWidth > DESKTOP_MODE_MIN_WIDTH) {
    return false
  }

  const viewportWidth = window.innerWidth

  if (viewportWidth > window.innerHeight) {
    return false
  }

  return viewportWidth >= DESKTOP_MODE_MIN_WIDTH && viewportWidth > deviceWidth * SCREEN_RATIO
}

function applyViewportLock() {
  const deviceWidth = getDeviceWidth()
  const root = document.documentElement

  if (shouldLockViewport(deviceWidth)) {
    root.style.zoom = String(window.innerWidth / deviceWidth)
    return
  }

  root.style.zoom = ''
}

export function initViewportLock() {
  applyViewportLock()

  window.addEventListener('resize', applyViewportLock)
  window.addEventListener('orientationchange', applyViewportLock)
}
