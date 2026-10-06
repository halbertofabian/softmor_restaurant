import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { Button } from '../../components/ui/Button'
import { Sheet } from '../../components/ui/Sheet'
import { UserIcon } from '../../components/ui/icons'
import { fetchWaiters } from '../../lib/api/tables'
import type { RestaurantTable } from '../../lib/api/types'

interface WaiterSheetProps {
  table: RestaurantTable
  branchId: number
  submitting: boolean
  onClose: () => void
  onConfirm: (waiterId: number) => void
}

export function WaiterSheet({ table, branchId, submitting, onClose, onConfirm }: WaiterSheetProps) {
  const [selectedId, setSelectedId] = useState<number | null>(null)

  const waitersQuery = useQuery({
    queryKey: ['waiters', branchId],
    queryFn: () => fetchWaiters(branchId),
  })

  const waiters = waitersQuery.data?.data ?? []

  return (
    <Sheet open title={`Asignar mesero · ${table.name}`} onClose={onClose}>
      <p className="mb-4 text-sm text-gray-400">
        Como administrador o caja, selecciona el mesero que atenderá esta mesa.
      </p>

      {waitersQuery.isLoading && (
        <div className="space-y-2">
          {Array.from({ length: 3 }).map((_, index) => (
            <div key={index} className="h-12 animate-pulse rounded-2xl bg-white/5" />
          ))}
        </div>
      )}

      {waitersQuery.isError && (
        <div className="rounded-2xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-sm text-red-300">
          No se pudo cargar la lista de meseros.
        </div>
      )}

      {!waitersQuery.isLoading && !waitersQuery.isError && waiters.length === 0 && (
        <div className="rounded-2xl border border-primary/20 bg-primary/10 px-4 py-3 text-sm text-primary">
          No hay meseros asignados a esta sucursal. Asigna uno desde el sistema web.
        </div>
      )}

      {waiters.length > 0 && (
        <ul className="space-y-2">
          {waiters.map((waiter) => {
            const isSelected = waiter.id === selectedId

            return (
              <li key={waiter.id}>
                <button
                  type="button"
                  onClick={() => setSelectedId(waiter.id)}
                  className={`flex w-full items-center gap-3 rounded-2xl border px-4 py-3 text-left transition ${
                    isSelected
                      ? 'border-primary/60 bg-primary/10'
                      : 'border-white/10 bg-white/5 hover:border-white/20'
                  }`}
                >
                  <span
                    className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full ${
                      isSelected ? 'bg-primary text-black' : 'bg-white/5 text-gray-400'
                    }`}
                  >
                    <UserIcon className="h-4 w-4" />
                  </span>
                  <span className="truncate text-sm font-medium text-white">{waiter.name}</span>
                </button>
              </li>
            )
          })}
        </ul>
      )}

      <div className="mt-5 flex justify-end gap-2">
        <Button variant="secondary" onClick={onClose} disabled={submitting}>
          Cancelar
        </Button>
        <Button
          onClick={() => selectedId && onConfirm(selectedId)}
          disabled={selectedId === null || submitting}
        >
          {submitting ? 'Abriendo…' : 'Abrir mesa'}
        </Button>
      </div>
    </Sheet>
  )
}
