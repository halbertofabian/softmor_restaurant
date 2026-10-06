import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { Badge } from '../../components/ui/Badge'
import { Card } from '../../components/ui/Card'
import {
  CheckIcon,
  DeviceIcon,
  HistoryIcon,
  MinusIcon,
  PrinterIcon,
  ShieldIcon,
  StoreIcon,
  TablesIcon,
  WifiIcon,
  WifiOffIcon,
} from '../../components/ui/icons'
import { getDeviceName } from '../../lib/device'
import { useOnlineStatus } from '../../lib/useOnlineStatus'
import { useAuthStore } from '../../stores/authStore'

function Stat({
  icon,
  label,
  value,
  hint,
  tone = 'default',
}: {
  icon: ReactNode
  label: string
  value: string
  hint?: string
  tone?: 'default' | 'success' | 'muted'
}) {
  const valueTone =
    tone === 'success' ? 'text-emerald-400' : tone === 'muted' ? 'text-gray-500' : 'text-white'

  return (
    <div className="rounded-3xl border border-white/5 bg-card p-4 shadow-xl shadow-black/20">
      <div className="flex items-center gap-2 text-gray-500">
        <span className="text-primary">{icon}</span>
        <span className="text-[11px] font-medium uppercase tracking-wide">{label}</span>
      </div>
      <p className={`mt-2 truncate text-sm font-semibold ${valueTone}`}>{value}</p>
      {hint && <p className="mt-0.5 truncate text-xs text-gray-500">{hint}</p>}
    </div>
  )
}

function PermissionRow({ label, enabled }: { label: string; enabled: boolean }) {
  return (
    <li className="flex items-center justify-between gap-4 py-2.5">
      <span className="text-sm text-gray-300">{label}</span>
      {enabled ? (
        <span className="flex h-5 w-5 items-center justify-center rounded-full bg-primary/15 text-primary">
          <CheckIcon className="h-3.5 w-3.5" />
        </span>
      ) : (
        <span className="flex h-5 w-5 items-center justify-center rounded-full bg-white/5 text-gray-600">
          <MinusIcon className="h-3.5 w-3.5" />
        </span>
      )}
    </li>
  )
}

export function DashboardPage() {
  const user = useAuthStore((state) => state.user)
  const role = useAuthStore((state) => state.role)
  const permissions = useAuthStore((state) => state.permissions)
  const branches = useAuthStore((state) => state.branches)
  const selectedBranchId = useAuthStore((state) => state.selectedBranchId)
  const online = useOnlineStatus()

  const branch = branches.find((item) => item.id === selectedBranchId)

  return (
    <div className="space-y-4">
      <section className="relative overflow-hidden rounded-3xl border border-white/5 bg-card p-5 shadow-xl shadow-black/20">
        <div className="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-primary/10 blur-3xl" />
        <p className="text-[11px] font-medium uppercase tracking-wider text-gray-500">
          Bienvenido
        </p>
        <h1 className="mt-1 truncate text-xl font-bold text-white">{user?.name}</h1>
        <div className="mt-3 flex flex-wrap items-center gap-2">
          <Badge variant="primary">{role}</Badge>
          <Badge>
            <StoreIcon className="h-3 w-3" />
            {branch?.name}
          </Badge>
        </div>
      </section>

      <div className="grid grid-cols-2 gap-3">
        <Stat
          icon={<StoreIcon className="h-4 w-4" />}
          label="Sucursal"
          value={branch?.name ?? '—'}
          hint={branch?.address || 'Sin dirección registrada'}
        />
        <Stat
          icon={<DeviceIcon className="h-4 w-4" />}
          label="Dispositivo"
          value={getDeviceName()}
          hint="Configuración local"
        />
        <Stat
          icon={online ? <WifiIcon className="h-4 w-4" /> : <WifiOffIcon className="h-4 w-4" />}
          label="Conexión"
          value={online ? 'En línea' : 'Sin conexión'}
          tone={online ? 'success' : 'muted'}
        />
        <Stat
          icon={<PrinterIcon className="h-4 w-4" />}
          label="Impresoras"
          value="Sin configurar"
          hint="Fase 3"
          tone="muted"
        />
      </div>

      <Card
        title="Mesas y comandas"
        icon={<TablesIcon className="h-4 w-4" />}
        action={<Badge variant="success">Disponible</Badge>}
      >
        <p className="text-sm text-gray-400">
          Consulta el mapa de mesas, abre una mesa y levanta la comanda. El envío se despacha por
          área de preparación.
        </p>
        <Link
          to="/mesas"
          className="mt-4 inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-black shadow-lg shadow-primary/20 transition hover:bg-primary-dark"
        >
          Ir a mesas
        </Link>
      </Card>

      <Card
        title="Historial y reimpresión"
        icon={<HistoryIcon className="h-4 w-4" />}
        action={<Badge variant="neutral">Reimprimir</Badge>}
      >
        <p className="text-sm text-gray-400">
          Consulta comandas recientes y reimprime tickets por área o completos.
        </p>
        <Link
          to="/historial"
          className="mt-4 inline-flex items-center justify-center rounded-xl border border-white/10 px-4 py-2.5 text-sm font-semibold text-gray-200 transition hover:bg-white/5"
        >
          Ver historial
        </Link>
      </Card>

      <Card title="Permisos" icon={<ShieldIcon className="h-4 w-4" />}>
        <ul className="divide-y divide-white/5">
          <PermissionRow label="Tomar comandas" enabled={permissions?.take_orders ?? false} />
          <PermissionRow label="Reimprimir comandas" enabled={permissions?.reprint ?? false} />
          <PermissionRow
            label="Configurar impresoras (local)"
            enabled={permissions?.manage_printers ?? false}
          />
        </ul>
      </Card>
    </div>
  )
}
