import { useOnlineStatus } from '../lib/useOnlineStatus'

export function OfflineBanner() {
  const online = useOnlineStatus()

  if (online) {
    return null
  }

  return (
    <div className="bg-primary px-4 py-2 text-center text-sm font-semibold text-black">
      Sin conexión. Los cambios se guardarán cuando vuelvas a estar en línea.
    </div>
  )
}
