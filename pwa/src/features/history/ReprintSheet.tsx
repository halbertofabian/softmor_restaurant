import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { Button } from '../../components/ui/Button'
import { Sheet } from '../../components/ui/Sheet'
import { fetchOrderPrintPayload } from '../../lib/api/orders'
import type { PrintPayload } from '../../lib/api/types'
import { printAndMark, reportPrintOutcome } from '../../lib/printing/printService'
import { useAuthStore } from '../../stores/authStore'

interface ReprintSheetProps {
  orderId: number
  title: string
  onClose: () => void
}

export function ReprintSheet({ orderId, title, onClose }: ReprintSheetProps) {
  const tenantId = useAuthStore((state) => state.tenantId)
  const branchId = useAuthStore((state) => state.selectedBranchId)
  const [printingKey, setPrintingKey] = useState<number | 'all' | null>(null)

  const payloadQuery = useQuery({
    queryKey: ['print-payload', orderId],
    queryFn: () => fetchOrderPrintPayload(orderId),
  })

  const payload = payloadQuery.data?.print

  async function handlePrint(areaId?: number) {
    if (!payload || !tenantId || !branchId) {
      return
    }

    setPrintingKey(areaId ?? 'all')

    try {
      const filtered: PrintPayload = areaId
        ? { ...payload, areas: payload.areas.filter((area) => area.area_id === areaId) }
        : payload

      const outcome = await printAndMark(tenantId, branchId, orderId, filtered, 'reprint', {
        allowPairing: true,
      })
      reportPrintOutcome(outcome)

      if (outcome.printed.length > 0 && outcome.failed.length === 0) {
        onClose()
      }
    } finally {
      setPrintingKey(null)
    }
  }

  return (
    <Sheet open title={`Reimprimir · ${title}`} onClose={onClose}>
      {payloadQuery.isLoading && (
        <div className="space-y-2">
          {Array.from({ length: 3 }).map((_, index) => (
            <div key={index} className="h-12 animate-pulse rounded-2xl bg-white/5" />
          ))}
        </div>
      )}

      {payloadQuery.isError && (
        <div className="space-y-3">
          <p className="text-sm text-gray-400">No se pudo cargar la comanda para reimprimir.</p>
          <Button variant="secondary" size="sm" onClick={() => payloadQuery.refetch()}>
            Reintentar
          </Button>
        </div>
      )}

      {payload && payload.areas.length === 0 && (
        <p className="text-sm text-gray-400">No hay áreas imprimibles en esta comanda.</p>
      )}

      {payload && payload.areas.length > 0 && (
        <>
          <Button
            className="w-full"
            onClick={() => handlePrint()}
            disabled={printingKey !== null}
          >
            {printingKey === 'all' ? 'Imprimiendo…' : 'Imprimir todo'}
          </Button>

          <ul className="mt-4 divide-y divide-white/5">
            {payload.areas.map((area) => (
              <li key={area.area_id} className="flex items-center justify-between gap-3 py-3">
                <div className="min-w-0">
                  <p className="truncate text-sm font-medium text-white">{area.area_name}</p>
                  <p className="text-[11px] text-gray-500">
                    {area.items.length} ítem(s) · {area.items.reduce((sum, item) => sum + item.quantity, 0)} unidad(es)
                  </p>
                </div>

                <Button
                  size="sm"
                  variant="secondary"
                  onClick={() => handlePrint(area.area_id)}
                  disabled={printingKey !== null}
                >
                  {printingKey === area.area_id ? 'Imprimiendo…' : 'Imprimir área'}
                </Button>
              </li>
            ))}
          </ul>
        </>
      )}
    </Sheet>
  )
}
