import { useNavigate } from 'react-router-dom'
import { Card } from '../../components/ui/Card'
import { ChevronRightIcon, LogoutIcon, StoreIcon, UserIcon } from '../../components/ui/icons'
import { logout } from '../../lib/api/auth'
import { useAuthStore } from '../../stores/authStore'

export function BranchSelectPage() {
  const navigate = useNavigate()
  const user = useAuthStore((state) => state.user)
  const branches = useAuthStore((state) => state.branches)
  const selectBranch = useAuthStore((state) => state.selectBranch)
  const clearSession = useAuthStore((state) => state.clearSession)

  function handleSelect(branchId: number) {
    selectBranch(branchId)
    navigate('/', { replace: true })
  }

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
    <div className="bg-grid relative flex min-h-screen flex-col items-center justify-center overflow-x-hidden bg-ink px-4 py-10">
      <div className="hero-mesh" />

      <div className="relative z-10 w-full max-w-md">
        <div className="mb-8 flex flex-col items-center gap-3 text-center">
          <img
            src="/pwa/gestionalfood.png"
            alt="GestionalFood"
            className="h-14 w-14 rounded-2xl shadow-lg"
          />
          <div>
            <h1 className="text-xl font-bold text-white">Selecciona sucursal</h1>
            <p className="mt-1 text-sm text-gray-400">
              Elige la sucursal donde vas a operar en este dispositivo.
            </p>
          </div>
        </div>

        <Card className="mb-4">
          <div className="flex items-center gap-3">
            <span className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary">
              <UserIcon className="h-5 w-5" />
            </span>
            <div className="min-w-0">
              <p className="truncate text-sm font-semibold text-white">{user?.name}</p>
              <p className="truncate text-xs text-gray-500">{user?.email}</p>
            </div>
          </div>
        </Card>

        {branches.length === 0 ? (
          <div className="rounded-2xl border border-primary/20 bg-primary/10 px-4 py-3 text-sm text-primary">
            No tienes sucursales activas asignadas. Contacta al administrador.
          </div>
        ) : (
          <ul className="space-y-2">
            {branches.map((branch) => (
              <li key={branch.id}>
                <button
                  type="button"
                  onClick={() => handleSelect(branch.id)}
                  className="group flex w-full items-center gap-3 rounded-2xl border border-white/5 bg-card px-4 py-3.5 text-left shadow-xl shadow-black/20 transition hover:border-primary/40"
                >
                  <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <StoreIcon className="h-5 w-5" />
                  </span>
                  <span className="min-w-0 flex-1">
                    <span className="block truncate text-sm font-semibold text-white">
                      {branch.name}
                    </span>
                    <span className="block truncate text-xs text-gray-500">
                      {branch.address || 'Sin dirección registrada'}
                    </span>
                  </span>
                  <ChevronRightIcon className="h-4 w-4 shrink-0 text-gray-600 transition group-hover:text-primary" />
                </button>
              </li>
            ))}
          </ul>
        )}

        <button
          type="button"
          onClick={handleLogout}
          className="mx-auto mt-8 flex items-center gap-2 text-sm font-medium text-gray-400 transition hover:text-white"
        >
          <LogoutIcon className="h-4 w-4" />
          Cerrar sesión
        </button>
      </div>
    </div>
  )
}
