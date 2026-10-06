import { useQuery } from '@tanstack/react-query'
import { useEffect } from 'react'
import { Link, Outlet, useLocation, useNavigate } from 'react-router-dom'
import { OfflineBanner } from '../components/OfflineBanner'
import { OutboxBanner } from '../components/OutboxBanner'
import { PullToRefresh } from '../components/PullToRefresh'
import { HomeIcon, LogoutIcon, PrinterIcon, TablesIcon } from '../components/ui/icons'
import { fetchMe, logout } from '../lib/api/auth'
import { startConnectionKeepAlive } from '../lib/printing/transport'
import { useAuthStore } from '../stores/authStore'

export function AppLayout() {
  const navigate = useNavigate()
  const location = useLocation()
  const token = useAuthStore((state) => state.token)
  const user = useAuthStore((state) => state.user)
  const branches = useAuthStore((state) => state.branches)
  const selectedBranchId = useAuthStore((state) => state.selectedBranchId)
  const applyMe = useAuthStore((state) => state.applyMe)
  const clearSession = useAuthStore((state) => state.clearSession)

  const { data: me } = useQuery({
    queryKey: ['me'],
    queryFn: fetchMe,
    enabled: Boolean(token),
  })

  useEffect(() => {
    if (me) {
      applyMe(me)
    }
  }, [me, applyMe])

  useEffect(() => startConnectionKeepAlive(), [])

  const branch = branches.find((item) => item.id === selectedBranchId)
  const inicioActive = location.pathname === '/'
  const mesasActive = location.pathname.startsWith('/mesas') || location.pathname.startsWith('/orders')
  const impresorasActive = location.pathname.startsWith('/impresoras')

  async function handleLogout() {
    try {
      await logout()
    } catch {
      // La sesión local se cierra igual aunque el servidor no responda.
    }

    clearSession()
    navigate('/login', { replace: true })
  }

  return (
    <div className="bg-grid relative flex min-h-screen flex-col bg-ink">
      <div className="hero-mesh" />

      <OfflineBanner />
      <OutboxBanner />

      <header className="sticky top-0 z-20 border-b border-white/5 bg-ink/80 backdrop-blur">
        <div className="mx-auto flex w-full max-w-3xl items-center justify-between gap-3 px-4 py-3 md:max-w-5xl xl:max-w-6xl">
          <div className="flex min-w-0 items-center gap-3">
            <img
              src="/pwa/gestionalfood.png"
              alt="GestionalFood"
              className="h-9 w-9 shrink-0 rounded-lg"
            />
            <div className="min-w-0">
              <p className="truncate text-sm font-semibold text-white">
                {branch?.name ?? 'Sucursal'}
              </p>
              <p className="truncate text-xs text-gray-500">
                {user?.name} · {user?.role}
              </p>
            </div>
          </div>

          <button
            type="button"
            onClick={handleLogout}
            className="flex shrink-0 items-center gap-1.5 rounded-lg border border-white/10 px-3 py-1.5 text-xs font-medium text-gray-300 transition hover:bg-white/5 hover:text-white"
          >
            <LogoutIcon className="h-3.5 w-3.5" />
            Salir
          </button>
        </div>
      </header>

      <main className="@container relative z-10 mx-auto w-full max-w-3xl flex-1 px-4 py-5 pb-28 md:max-w-5xl xl:max-w-6xl">
        <PullToRefresh>
          <Outlet />
        </PullToRefresh>
      </main>

      <nav className="fixed inset-x-0 bottom-0 z-20 border-t border-white/5 bg-ink/95 pb-[env(safe-area-inset-bottom)] backdrop-blur">
        <div className="mx-auto grid w-full max-w-3xl grid-cols-3 md:max-w-5xl xl:max-w-6xl">
          <Link
            to="/"
            className={`flex flex-col items-center gap-1 py-3 text-xs font-medium transition ${
              inicioActive ? 'text-primary' : 'text-gray-500 hover:text-gray-300'
            }`}
          >
            <HomeIcon className="h-5 w-5" />
            Inicio
          </Link>

          <Link
            to="/mesas"
            className="flex flex-col items-center gap-1 pb-1.5 pt-2 text-xs font-medium"
          >
            <span
              className={`flex h-11 w-11 items-center justify-center rounded-full transition ${
                mesasActive
                  ? 'bg-primary text-black shadow-lg shadow-primary/30'
                  : 'border border-white/10 bg-white/5 text-gray-400'
              }`}
            >
              <TablesIcon className="h-5 w-5" />
            </span>
            <span className={mesasActive ? 'text-primary' : 'text-gray-500'}>Mesas</span>
          </Link>

          <Link
            to="/impresoras"
            className={`flex flex-col items-center gap-1 py-3 text-xs font-medium transition ${
              impresorasActive ? 'text-primary' : 'text-gray-500 hover:text-gray-300'
            }`}
          >
            <PrinterIcon className="h-5 w-5" />
            Impresoras
          </Link>
        </div>
      </nav>
    </div>
  )
}
