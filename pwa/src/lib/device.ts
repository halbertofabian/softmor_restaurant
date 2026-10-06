const STORAGE_KEY = 'gestionalfood.device_name'

export function getDeviceName(): string {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    if (stored) {
      return stored
    }

    const name = detectDeviceName()
    localStorage.setItem(STORAGE_KEY, name)

    return name
  } catch {
    return 'Dispositivo PWA'
  }
}

function detectDeviceName(): string {
  const ua = navigator.userAgent

  const platform = /android/i.test(ua)
    ? 'Android'
    : /iphone|ipad|ipod/i.test(ua)
      ? 'iOS'
      : /windows/i.test(ua)
        ? 'Windows'
        : /macintosh|mac os/i.test(ua)
          ? 'Mac'
          : 'Dispositivo'

  const browser = /edg/i.test(ua)
    ? 'Edge'
    : /chrome|crios/i.test(ua)
      ? 'Chrome'
      : /firefox|fxios/i.test(ua)
        ? 'Firefox'
        : /safari/i.test(ua)
          ? 'Safari'
          : 'PWA'

  return `${platform} ${browser}`
}
