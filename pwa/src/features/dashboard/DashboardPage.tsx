import { useQuery } from '@tanstack/react-query'
import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import {
  AlertIcon,
  CurrencyIcon,
  ReceiptIcon,
  SparkIcon,
  StoreIcon,
  TablesIcon,
  TicketIcon,
  TrendingUpIcon,
  UsersIcon,
} from '../../components/ui/icons'
import { fetchDashboard, fetchWaiterDashboard } from '../../lib/api/dashboard'
import { formatDateTime, formatMoney } from '../../lib/format'
import { useAuthStore } from '../../stores/authStore'

const MONTHS = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic']

const orderStatus: Record<
  string,
  { label: string; variant: 'primary' | 'neutral' | 'success' | 'muted' }
> = {
  open: { label: 'Abierto', variant: 'primary' },
  sent: { label: 'Enviado', variant: 'neutral' },
  in_preparation: { label: 'En preparación', variant: 'neutral' },
  closed: { label: 'Cerrado', variant: 'success' },
  canceled: { label: 'Cancelado', variant: 'muted' },
}

function StatCard({
  icon,
  label,
  value,
  hint,
  tone = 'default',
  progress,
}: {
  icon: ReactNode
  label: string
  value: string
  hint?: string
  tone?: 'default' | 'success' | 'muted'
  progress?: number
}) {
  const valueTone =
    tone === 'success' ? 'text-emerald-400' : tone === 'muted' ? 'text-gray-500' : 'text-white'

  return (
    <div className="rounded-3xl border border-white/5 bg-card p-4 shadow-xl shadow-black/20">
      <div className="flex items-center gap-2 text-gray-500">
        <span className="text-primary">{icon}</span>
        <span className="text-[11px] font-medium uppercase tracking-wide">{label}</span>
      </div>
      <p className={`mt-2 truncate text-lg font-bold ${valueTone}`}>{value}</p>
      {hint && <p className="mt-0.5 truncate text-xs text-gray-500">{hint}</p>}
      {progress !== undefined && (
        <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-white/5">
          <div
            className="h-full rounded-full bg-primary"
            style={{ width: `${Math.min(Math.max(progress, 0), 100)}%` }}
          />
        </div>
      )}
    </div>
  )
}

function MonthlySalesChart({ year, values }: { year: number; values: number[] }) {
  const max = Math.max(...values, 1)
  const total = values.reduce((sum, value) => sum + value, 0)

  return (
    <Card
      title={`Ventas ${year}`}
      icon={<TrendingUpIcon className="h-4 w-4" />}
      action={<span className="text-xs font-semibold text-primary">{formatMoney(total)}</span>}
    >
      <div className="flex h-36 items-end gap-1">
        {values.map((value, index) => {
          const height = value > 0 ? Math.max((value / max) * 100, 4) : 2

          return (
            <div
              key={MONTHS[index]}
              className="flex h-full flex-1 flex-col items-center justify-end gap-1"
            >
              <div
                className={`w-full rounded-t ${
                  value > 0 ? 'bg-gradient-to-t from-primary/30 to-primary' : 'bg-white/5'
                }`}
                style={{ height: `${height}%` }}
                title={formatMoney(value)}
              />
              <span className="text-[9px] font-medium uppercase text-gray-500">
                {MONTHS[index]}
              </span>
            </div>
          )
        })}
      </div>
    </Card>
  )
}

function AdminDashboard() {
  const branchId = useAuthStore((state) => state.selectedBranchId)

  const dashboardQuery = useQuery({
    queryKey: ['dashboard', branchId],
    queryFn: () => fetchDashboard(branchId as number),
    enabled: Boolean(branchId),
    refetchOnMount: 'always',
  })

  if (dashboardQuery.isLoading) {
    return (
      <div className="space-y-4">
        <div className="grid grid-cols-2 gap-3 @2xl:grid-cols-4">
          {Array.from({ length: 4 }).map((_, index) => (
            <div
              key={index}
              className="h-24 animate-pulse rounded-3xl border border-white/5 bg-card"
            />
          ))}
        </div>
        <div className="h-52 animate-pulse rounded-3xl border border-white/5 bg-card" />
      </div>
    )
  }

  if (dashboardQuery.isError || !dashboardQuery.data) {
    return (
      <Card>
        <p className="text-sm text-gray-400">No se pudo cargar el dashboard.</p>
        <Button
          variant="secondary"
          size="sm"
          className="mt-3"
          onClick={() => dashboardQuery.refetch()}
        >
          Reintentar
        </Button>
      </Card>
    )
  }

  const stats = dashboardQuery.data.data
  const occupancy =
    stats.active_tables > 0 ? (stats.occupied_tables / stats.active_tables) * 100 : 0

  return (
    <>
      <div className="grid grid-cols-2 gap-3 @2xl:grid-cols-4">
        <StatCard
          icon={<CurrencyIcon className="h-4 w-4" />}
          label="Ventas hoy"
          value={formatMoney(stats.sales_today, 0)}
          tone="success"
        />
        <StatCard
          icon={<ReceiptIcon className="h-4 w-4" />}
          label="Pedidos"
          value={String(stats.orders_today)}
          hint="Total registrados"
        />
        <StatCard
          icon={<UsersIcon className="h-4 w-4" />}
          label="Mesas ocupadas"
          value={`${stats.occupied_tables}/${stats.active_tables}`}
          progress={occupancy}
        />
        <StatCard
          icon={<TicketIcon className="h-4 w-4" />}
          label="Ticket promedio"
          value={formatMoney(stats.avg_ticket)}
        />
      </div>

      <MonthlySalesChart year={stats.sales_year} values={stats.sales_by_month} />

      <div className="grid grid-cols-1 gap-4 @4xl:grid-cols-2">
        <Card title="Últimos pedidos" icon={<ReceiptIcon className="h-4 w-4" />}>
          {stats.latest_orders.length === 0 ? (
            <p className="text-sm text-gray-400">Aún no hay pedidos registrados.</p>
          ) : (
            <ul className="divide-y divide-white/5">
              {stats.latest_orders.map((order) => {
                const status = orderStatus[order.status] ?? {
                  label: order.status,
                  variant: 'neutral' as const,
                }

                return (
                  <li key={order.id} className="flex items-center justify-between gap-3 py-2.5">
                    <div className="min-w-0">
                      <p className="truncate text-sm font-semibold text-white">
                        {order.table_name ?? `Comanda #${order.id}`}
                      </p>
                      <p className="truncate text-[11px] text-gray-500">
                        #{order.id} · {formatDateTime(order.created_at)}
                      </p>
                    </div>
                    <div className="flex shrink-0 items-center gap-2">
                      <span className="text-sm font-bold text-white">
                        {formatMoney(order.total)}
                      </span>
                      <Badge variant={status.variant}>{status.label}</Badge>
                    </div>
                  </li>
                )
              })}
            </ul>
          )}
        </Card>

        <Card title="Productos más vendidos" icon={<SparkIcon className="h-4 w-4" />}>
          {stats.top_products.length === 0 ? (
            <p className="text-sm text-gray-400">Aún no hay productos vendidos.</p>
          ) : (
            <ul className="divide-y divide-white/5">
              {stats.top_products.map((product) => (
                <li
                  key={product.product_name}
                  className="flex items-center justify-between gap-3 py-2.5"
                >
                  <div className="min-w-0">
                    <p className="truncate text-sm font-medium text-gray-200">
                      {product.product_name}
                    </p>
                    <p className="text-[11px] text-gray-500">{product.quantity} vendidos</p>
                  </div>
                  <span className="shrink-0 text-sm font-bold text-primary">
                    {formatMoney(product.total)}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </Card>
      </div>

      <section className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-primary to-primary-dark p-5 text-black shadow-xl shadow-primary/20">
        <div className="flex items-center justify-between gap-3">
          <div>
            <h2 className="text-sm font-bold">Resumen de comandas</h2>
            <p className="text-xs font-medium text-black/70">Total procesado</p>
          </div>
          <p className="text-xl font-bold">{formatMoney(stats.total_orders_value)}</p>
        </div>
      </section>
    </>
  )
}

function WaiterDashboard() {
  const branchId = useAuthStore((state) => state.selectedBranchId)

  const dashboardQuery = useQuery({
    queryKey: ['dashboard', 'waiter', branchId],
    queryFn: () => fetchWaiterDashboard(branchId as number),
    enabled: Boolean(branchId),
    refetchOnMount: 'always',
  })

  if (dashboardQuery.isLoading) {
    return (
      <div className="space-y-4">
        <div className="grid grid-cols-2 gap-3 @2xl:grid-cols-4">
          {Array.from({ length: 4 }).map((_, index) => (
            <div
              key={index}
              className="h-24 animate-pulse rounded-3xl border border-white/5 bg-card"
            />
          ))}
        </div>
        <div className="h-40 animate-pulse rounded-3xl border border-white/5 bg-card" />
      </div>
    )
  }

  if (dashboardQuery.isError || !dashboardQuery.data) {
    return (
      <Card>
        <p className="text-sm text-gray-400">No se pudo cargar tu resumen.</p>
        <Button
          variant="secondary"
          size="sm"
          className="mt-3"
          onClick={() => dashboardQuery.refetch()}
        >
          Reintentar
        </Button>
      </Card>
    )
  }

  const stats = dashboardQuery.data.data

  return (
    <>
      <div className="grid grid-cols-2 gap-3 @2xl:grid-cols-4">
        <StatCard
          icon={<TablesIcon className="h-4 w-4" />}
          label="Mis mesas"
          value={String(stats.active_orders)}
          hint="Comandas abiertas"
        />
        <StatCard
          icon={<ReceiptIcon className="h-4 w-4" />}
          label="Comandas hoy"
          value={String(stats.orders_today)}
        />
        <StatCard
          icon={<AlertIcon className="h-4 w-4" />}
          label="Pendientes"
          value={String(stats.pending_items)}
          hint="Por enviar a cocina"
          tone={stats.pending_items > 0 ? 'default' : 'muted'}
        />
        <StatCard
          icon={<CurrencyIcon className="h-4 w-4" />}
          label="Vendido hoy"
          value={formatMoney(stats.sales_today, 0)}
          hint="Solo comandas cobradas"
          tone="success"
        />
      </div>

      <Card
        title="Mis mesas abiertas"
        icon={<TablesIcon className="h-4 w-4" />}
        action={
          stats.pending_items > 0 ? (
            <Badge variant="primary">{stats.pending_items} pendientes</Badge>
          ) : undefined
        }
      >
        {stats.open_orders.length === 0 ? (
          <>
            <p className="text-sm text-gray-400">
              No tienes mesas abiertas. Abre una para comenzar a tomar la comanda.
            </p>
            <Link
              to="/mesas"
              className="mt-4 inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-black shadow-lg shadow-primary/20 transition hover:bg-primary-dark"
            >
              Ir a mesas
            </Link>
          </>
        ) : (
          <ul className="divide-y divide-white/5">
            {stats.open_orders.map((order) => (
              <li key={order.id}>
                <Link
                  to={`/orders/${order.id}`}
                  className="flex items-center justify-between gap-3 py-3 transition hover:opacity-80"
                >
                  <div className="min-w-0">
                    <p className="truncate text-sm font-semibold text-white">
                      {order.table_name ?? `Comanda #${order.id}`}
                    </p>
                    <p className="truncate text-[11px] text-gray-500">
                      #{order.id} · {formatDateTime(order.created_at)}
                    </p>
                  </div>
                  <div className="flex shrink-0 items-center gap-2">
                    <span className="text-sm font-bold text-white">
                      {formatMoney(order.total)}
                    </span>
                    {order.pending_items > 0 ? (
                      <Badge variant="primary">{order.pending_items} pend.</Badge>
                    ) : (
                      <Badge variant="muted">Todo enviado</Badge>
                    )}
                  </div>
                </Link>
              </li>
            ))}
          </ul>
        )}
      </Card>
    </>
  )
}

export function DashboardPage() {
  const user = useAuthStore((state) => state.user)
  const role = useAuthStore((state) => state.role)
  const permissions = useAuthStore((state) => state.permissions)
  const branches = useAuthStore((state) => state.branches)
  const selectedBranchId = useAuthStore((state) => state.selectedBranchId)

  const branch = branches.find((item) => item.id === selectedBranchId)
  const isAdmin = role === 'administrador' || role === 'admin'
  const showWaiterDashboard = !isAdmin && (permissions?.take_orders ?? false)

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
        <p className="mt-3 text-[10px] text-gray-500">Versión {__APP_VERSION__}</p>
      </section>

      {isAdmin ? <AdminDashboard /> : showWaiterDashboard ? <WaiterDashboard /> : null}
    </div>
  )
}
