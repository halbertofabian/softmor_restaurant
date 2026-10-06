import { useLiveQuery } from 'dexie-react-hooks'
import { useEffect } from 'react'
import { countOutbox } from '../lib/db/activity'
import { flushOutbox } from '../lib/sync/outbox'
import { useOnlineStatus } from '../lib/useOnlineStatus'

export function OutboxBanner() {
  const online = useOnlineStatus()
  const pending = useLiveQuery(() => countOutbox(), []) ?? 0

  useEffect(() => {
    if (online) {
      void flushOutbox()
    }
  }, [online])

  useEffect(() => {
    const interval = window.setInterval(() => {
      void flushOutbox()
    }, 30_000)

    return () => window.clearInterval(interval)
  }, [])

  if (pending === 0) {
    return null
  }

  return (
    <div className="bg-primary/15 px-4 py-1.5 text-center text-[11px] font-medium text-primary">
      {pending} comanda(s) pendiente(s) de envío{online ? '' : ' · sin conexión'}
    </div>
  )
}
