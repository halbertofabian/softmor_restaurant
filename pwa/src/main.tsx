import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App'
import { initPwaInstall } from './lib/pwa/install'
import { initViewportLock } from './lib/viewportLock'

initPwaInstall()
initViewportLock()

// iOS Safari ignora user-scalable=no; bloqueamos el pinch-zoom por gesto.
document.addEventListener('gesturestart' as keyof DocumentEventMap, (event) => {
  event.preventDefault()
})

// Evita que el navegador desaloje IndexedDB (configuración de impresoras y outbox).
if (navigator.storage?.persist) {
  void navigator.storage.persist().catch(() => undefined)
}

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
