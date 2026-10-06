import { useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { HistoryIcon, RefreshIcon, StoreIcon, TablesIcon } from '../../components/ui/icons'
import { fetchTables, getOrCreateOrder } from '../../lib/api/tables'
import type { RestaurantTable, TableStatus } from '../../lib/api/types'
import { useAuthStore } from '../../stores/authStore'
import { useToastStore } from '../../stores/toastStore'
import { WaiterSheet } from './WaiterSheet'

const cardStyles: Record<TableStatus, string> = {
  free: 'border-white/10 bg-card hover:border-primary/50',
  occupied: 'border-primary/40 bg-primary/10 hover:border-primary/70',
  reserved: 'border-sky-500/30 bg-sky-500/10',
  inactive: 'border-white/5 bg-card opacity-40',
}

const dotStyles: Record<TableStatus, string> = {
  free: 'bg-emerald-400',
  occupied: 'bg-primary',
  reserved: 'bg-sky-400',
  inactive: 'bg-gray-600',
}

const statusLabels: Record<TableStatus, string> = {
  free: 'Libre',
  occupied: 'Ocupada',
  reserved: 'Reservada',
  inactive: 'Inactiva',
}

function groupByZone(tables: RestaurantTable[]) {
  const groups = new Map<string, RestaurantTable[]>()

  for (const table of tables) {
    const zone = table.zone?.trim() || 'General'
    const list = groups.get(zone) ?? []
    list.push(table)
    groups.set(zone, list)
  }

  return [...groups.entries()].map(([zone, items]) => ({ zone, tables: items }))
}

export function TablesPage() {
  const branchId = useAuthStore((state) => state.selectedBranchId)
  const branches = useAuthStore((state) => state.branches)
  const user = useAuthStore((state) => state.user)
  const role = useAuthStore((state) => state.role)
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const pushToast = useToastStore((state) => state.push)
  const [openingTableId, setOpeningTableId] = useState<number | null>(null)
  const [waiterTable, setWaiterTable] = useState<RestaurantTable | null>(null)

  const isMesero = role === 'mesero'
  const branch = branches.find((item) => item.id === branchId)

  const tablesQuery = useQuery({
    queryKey: ['tables', branchId],
    queryFn: () => fetchTables(branchId as number),
    enabled: Boolean(branchId),
    refetchOnMount: 'always',
  })

  const tables = tablesQuery.data?.data ?? []
  const freeCount = tables.filter((table) => table.status === 'free').length
  const occupiedCount = tables.filter((table) => table.status === 'occupied').length
  const zones = groupByZone(tables)

  async function openTable(table: RestaurantTable, waiterId?: number) {
    if (!branchId) {
      return
    }

    setOpeningTableId(table.id)

    try {
      const response = await getOrCreateOrder(table.id, branchId, waiterId)
      await queryClient.invalidateQueries({ queryKey: ['tables', branchId] })
      setWaiterTable(null)
      navigate(`/orders/${response.order.id}`)
    } catch (error) {
      pushToast(
        error instanceof Error ? error.message : 'No se pudo abrir la mesa. Intenta de nuevo.',
        'error',
      )
    } finally {
      setOpeningTableId(null)
    }
  }

  function handleTableClick(table: RestaurantTable) {
    if (table.status === 'inactive' || openingTableId !== null) {
      return
    }

    if (table.active_order_id) {
      const isForeignWaiter =
        isMesero && table.active_order_waiter_id !== null && table.active_order_waiter_id !== user?.id

      if (isForeignWaiter) {
        pushToast(
          `Mesa atendida por ${table.active_order_waiter_name ?? 'otro mesero'}.`,
          'error',
        )
        return
      }

      navigate(`/orders/${table.active_order_id}`)
      return
    }

    if (isMesero) {
      void openTable(table)
      return
    }

    setWaiterTable(table)
  }

  return (
    <div className="space-y-4">
      <Card>
        <div className="flex items-center justify-between gap-3">
          <div className="flex items-center gap-3">
            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
              <TablesIcon className="h-5 w-5" />
            </span>
            <div>
              <h1 className="text-lg font-bold text-white">Mesas</h1>
              <p className="flex items-center gap-1 text-xs text-gray-500">
                <StoreIcon className="h-3 w-3" />
                {branch?.name}
              </p>
            </div>
          </div>

          <div className="flex shrink-0 items-center gap-2">
            <Link
              to="/historial"
              className="rounded-xl border border-white/10 p-2 text-gray-400 transition hover:bg-white/5 hover:text-white"
              aria-label="Historial de comandas"
            >
              <HistoryIcon className="h-4 w-4" />
            </Link>

            <button
              type="button"
              onClick={() => tablesQuery.refetch()}
              disabled={tablesQuery.isFetching}
              className="rounded-xl border border-white/10 p-2 text-gray-400 transition hover:bg-white/5 hover:text-white disabled:opacity-50"
              aria-label="Actualizar mesas"
            >
              <RefreshIcon className={`h-4 w-4 ${tablesQuery.isFetching ? 'animate-spin' : ''}`} />
            </button>
          </div>
        </div>

        <div className="mt-4 flex flex-wrap items-center gap-2">
          <Badge variant="success">{freeCount} libres</Badge>
          <Badge variant="primary">{occupiedCount} ocupadas</Badge>
          {tables.length > 0 && <Badge variant="muted">{tables.length} en total</Badge>}
        </div>
      </Card>

      {tablesQuery.isLoading && (
        <div className="grid grid-cols-2 gap-3 @2xl:grid-cols-3 @5xl:grid-cols-4">
          {Array.from({ length: 6 }).map((_, index) => (
            <div
              key={index}
              className="h-24 animate-pulse rounded-3xl border border-white/5 bg-card"
            />
          ))}
        </div>
      )}

      {tablesQuery.isError && (
        <Card>
          <p className="text-sm text-gray-400">No se pudieron cargar las mesas.</p>
          <Button
            variant="secondary"
            size="sm"
            className="mt-3"
            onClick={() => tablesQuery.refetch()}
          >
            Reintentar
          </Button>
        </Card>
      )}

      {!tablesQuery.isLoading && !tablesQuery.isError && tables.length === 0 && (
        <Card>
          <p className="text-sm text-gray-400">
            No hay mesas registradas en esta sucursal. Créalas desde el sistema web.
          </p>
        </Card>
      )}

      {zones.map(({ zone, tables: zoneTables }) => (
        <section key={zone} className="space-y-2">
          <h2 className="px-1 text-[11px] font-semibold uppercase tracking-wider text-gray-500">
            {zone}
          </h2>

          <div className="grid grid-cols-2 gap-3 @2xl:grid-cols-3 @5xl:grid-cols-4">
            {zoneTables.map((table) => {
              const isOpening = openingTableId === table.id
              const isOwnTable =
                table.active_order_waiter_id !== null && table.active_order_waiter_id === user?.id
              const isForeignTable =
                isMesero &&
                table.active_order_waiter_id !== null &&
                table.active_order_waiter_id !== user?.id

              return (
                <button
                  key={table.id}
                  type="button"
                  disabled={table.status === 'inactive' || isOpening}
                  onClick={() => handleTableClick(table)}
                  className={`rounded-3xl border p-4 text-left shadow-xl shadow-black/20 transition disabled:cursor-not-allowed ${cardStyles[table.status]}`}
                >
                  <div className="flex items-start justify-between gap-2">
                    <span className="truncate text-sm font-semibold text-white">{table.name}</span>
                    <span className={`mt-1 h-2 w-2 shrink-0 rounded-full ${dotStyles[table.status]}`} />
                  </div>

                  <p className="mt-3 text-[11px] text-gray-500">
                    {isOpening
                      ? 'Abriendo…'
                      : table.seats !== null
                        ? `${statusLabels[table.status]} · ${table.seats} asientos`
                        : statusLabels[table.status]}
                  </p>

                  {table.active_order_id && (
                    <p className="mt-1 truncate text-[11px] font-medium text-primary">
                      {isForeignTable
                        ? `Atiende ${table.active_order_waiter_name ?? 'otro mesero'}`
                        : isOwnTable
                          ? 'Tu mesa · comanda activa'
                          : `Atiende ${table.active_order_waiter_name ?? 'mesero'}`}
                    </p>
                  )}
                </button>
              )
            })}
          </div>
        </section>
      ))}

      {waiterTable && branchId && (
        <WaiterSheet
          table={waiterTable}
          branchId={branchId}
          submitting={openingTableId === waiterTable.id}
          onClose={() => setWaiterTable(null)}
          onConfirm={(waiterId) => openTable(waiterTable, waiterId)}
        />
      )}
    </div>
  )
}
