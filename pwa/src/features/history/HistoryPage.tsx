import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { ChevronLeftIcon, HistoryIcon, PrinterIcon } from '../../components/ui/icons'
import { fetchOrders } from '../../lib/api/orders'
import { formatDateTime, formatMoney } from '../../lib/format'
import { useAuthStore } from '../../stores/authStore'
import { ReprintSheet } from './ReprintSheet'

type StatusFilter = 'all' | 'open' | 'closed'

const filters: { value: StatusFilter; label: string }[] = [
  { value: 'all', label: 'Todas' },
  { value: 'open', label: 'Abiertas' },
  { value: 'closed', label: 'Cerradas' },
]

export function HistoryPage() {
  const branchId = useAuthStore((state) => state.selectedBranchId)
  const [status, setStatus] = useState<StatusFilter>('all')
  const [page, setPage] = useState(1)
  const [reprint, setReprint] = useState<{ id: number; title: string } | null>(null)

  const ordersQuery = useQuery({
    queryKey: ['orders', branchId, status, page],
    queryFn: () =>
      fetchOrders({
        branchId: branchId as number,
        status: status === 'all' ? undefined : status,
        page,
      }),
    enabled: Boolean(branchId),
    placeholderData: (previous) => previous,
  })

  const orders = ordersQuery.data?.data ?? []
  const meta = ordersQuery.data?.meta

  return (
    <div className="space-y-4">
      <div className="flex items-center gap-3">
        <Link
          to="/"
          className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/10 text-gray-300 transition hover:bg-white/5"
          aria-label="Volver al inicio"
        >
          <ChevronLeftIcon className="h-4 w-4" />
        </Link>

        <div className="flex min-w-0 flex-1 items-center gap-2">
          <HistoryIcon className="h-5 w-5 text-primary" />
          <div className="min-w-0">
            <h1 className="truncate text-lg font-bold text-white">Historial</h1>
            <p className="text-xs text-gray-500">Comandas recientes y reimpresión</p>
          </div>
        </div>
      </div>

      <div className="flex gap-2">
        {filters.map((filter) => (
          <button
            key={filter.value}
            type="button"
            onClick={() => {
              setStatus(filter.value)
              setPage(1)
            }}
            className={`rounded-full border px-3.5 py-1.5 text-xs font-medium transition ${
              status === filter.value
                ? 'border-primary bg-primary/15 text-primary'
                : 'border-white/10 text-gray-400 hover:text-white'
            }`}
          >
            {filter.label}
          </button>
        ))}
      </div>

      {ordersQuery.isLoading && (
        <div className="space-y-2">
          {Array.from({ length: 4 }).map((_, index) => (
            <div key={index} className="h-20 animate-pulse rounded-2xl border border-white/5 bg-card" />
          ))}
        </div>
      )}

      {ordersQuery.isError && (
        <Card>
          <p className="text-sm text-gray-400">No se pudo cargar el historial.</p>
          <Button
            variant="secondary"
            size="sm"
            className="mt-3"
            onClick={() => ordersQuery.refetch()}
          >
            Reintentar
          </Button>
        </Card>
      )}

      {!ordersQuery.isLoading && !ordersQuery.isError && orders.length === 0 && (
        <Card>
          <p className="text-sm text-gray-400">No hay comandas para este filtro.</p>
        </Card>
      )}

      {orders.length > 0 && (
        <ul className="space-y-2">
          {orders.map((order) => (
            <li
              key={order.id}
              className="flex items-center justify-between gap-3 rounded-2xl border border-white/5 bg-card p-4 shadow-xl shadow-black/20"
            >
              <div className="min-w-0">
                <div className="flex flex-wrap items-center gap-2">
                  <p className="truncate text-sm font-semibold text-white">
                    {order.table_name ?? `Comanda #${order.id}`}
                  </p>
                  <Badge variant={order.status === 'open' ? 'primary' : 'muted'}>
                    {order.status === 'open' ? 'Abierta' : 'Cerrada'}
                  </Badge>
                  {order.has_pending && <Badge variant="success">Pendientes</Badge>}
                </div>
                <p className="mt-1 truncate text-[11px] text-gray-500">
                  #{order.id} · {order.waiter_name ?? 'Sin mesero'} · {formatDateTime(order.created_at)}
                </p>
              </div>

              <div className="flex shrink-0 items-center gap-2">
                <p className="text-sm font-bold text-white">{formatMoney(order.total)}</p>
                <Button
                  size="sm"
                  variant="secondary"
                  onClick={() =>
                    setReprint({
                      id: order.id,
                      title: order.table_name ?? `Comanda #${order.id}`,
                    })
                  }
                >
                  <PrinterIcon className="h-3.5 w-3.5" />
                  Reimprimir
                </Button>
              </div>
            </li>
          ))}
        </ul>
      )}

      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-between gap-3">
          <Button
            variant="secondary"
            size="sm"
            disabled={page <= 1}
            onClick={() => setPage((current) => Math.max(1, current - 1))}
          >
            Anterior
          </Button>
          <span className="text-xs text-gray-500">
            Página {meta.current_page} de {meta.last_page}
          </span>
          <Button
            variant="secondary"
            size="sm"
            disabled={page >= meta.last_page}
            onClick={() => setPage((current) => current + 1)}
          >
            Siguiente
          </Button>
        </div>
      )}

      {reprint && (
        <ReprintSheet
          orderId={reprint.id}
          title={reprint.title}
          onClose={() => setReprint(null)}
        />
      )}
    </div>
  )
}
