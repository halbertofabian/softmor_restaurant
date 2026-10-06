import { useMutation } from '@tanstack/react-query'
import { Badge } from '../../components/ui/Badge'
import { MinusIcon, PlusIcon, TrashIcon } from '../../components/ui/icons'
import { removeOrderItem, updateOrderItem } from '../../lib/api/orders'
import type { OrderDetail } from '../../lib/api/types'
import { formatMoney } from '../../lib/format'
import { useToastStore } from '../../stores/toastStore'

interface OrderItemsListProps {
  orderId: number
  details: OrderDetail[]
  isCombo: (productId: number) => boolean
  onChanged: () => void
}

interface DetailRowProps {
  detail: OrderDetail
  isCombo: boolean
  updating: boolean
  removing: boolean
  onUpdate: (detailId: number, quantity: number) => void
  onRemove: (detailId: number) => void
}

function DetailRow({
  detail,
  isCombo,
  updating,
  removing,
  onUpdate,
  onRemove,
}: DetailRowProps) {
  const isPending = detail.status === 'pending'
  const canEditQuantity = isPending && !isCombo
  const lineTotal = Number(detail.price) * detail.quantity

  return (
    <li className="flex items-start gap-3 py-3">
      <div className="min-w-0 flex-1">
        <p className="text-sm font-medium text-white">
          {detail.product_name}
          {detail.flavor_name && <span className="text-gray-500"> · {detail.flavor_name}</span>}
        </p>

        {detail.notes && <p className="mt-0.5 text-xs text-gray-500">{detail.notes}</p>}

        <p className="mt-1 text-xs text-gray-500">
          {formatMoney(detail.price)} c/u
          {detail.preparation_area_name && ` · ${detail.preparation_area_name}`}
        </p>
      </div>

      <div className="flex shrink-0 flex-col items-end gap-2">
        <p className="text-sm font-semibold text-white">{formatMoney(lineTotal)}</p>

        {isPending ? (
          <div className="flex items-center gap-2">
            {canEditQuantity ? (
              <div className="flex items-center rounded-lg border border-white/10">
                <button
                  type="button"
                  disabled={updating || removing}
                  onClick={() => onUpdate(detail.id, Math.max(1, detail.quantity - 1))}
                  className="p-1.5 text-gray-400 transition hover:text-white disabled:opacity-40"
                  aria-label="Quitar una unidad"
                >
                  <MinusIcon className="h-3.5 w-3.5" />
                </button>
                <span className="w-6 text-center text-xs font-semibold text-white">
                  {detail.quantity}
                </span>
                <button
                  type="button"
                  disabled={updating || removing}
                  onClick={() => onUpdate(detail.id, Math.min(99, detail.quantity + 1))}
                  className="p-1.5 text-gray-400 transition hover:text-white disabled:opacity-40"
                  aria-label="Agregar una unidad"
                >
                  <PlusIcon className="h-3.5 w-3.5" />
                </button>
              </div>
            ) : (
              <Badge variant="neutral">{detail.quantity}×</Badge>
            )}

            <button
              type="button"
              disabled={updating || removing}
              onClick={() => onRemove(detail.id)}
              className="rounded-lg p-1.5 text-gray-500 transition hover:bg-red-500/10 hover:text-red-400 disabled:opacity-40"
              aria-label="Eliminar producto"
            >
              <TrashIcon className="h-3.5 w-3.5" />
            </button>
          </div>
        ) : (
          <div className="flex items-center gap-2">
            <Badge variant="muted">{detail.quantity}×</Badge>
            <Badge variant="success">Enviado</Badge>
          </div>
        )}
      </div>
    </li>
  )
}

export function OrderItemsList({ orderId, details, isCombo, onChanged }: OrderItemsListProps) {
  const pushToast = useToastStore((state) => state.push)

  const updateMutation = useMutation({
    mutationFn: ({ detailId, quantity }: { detailId: number; quantity: number }) =>
      updateOrderItem(orderId, detailId, { quantity }),
    onSuccess: () => onChanged(),
    onError: (error) =>
      pushToast(error instanceof Error ? error.message : 'No se pudo actualizar', 'error'),
  })

  const removeMutation = useMutation({
    mutationFn: (detailId: number) => removeOrderItem(orderId, detailId),
    onSuccess: () => onChanged(),
    onError: (error) =>
      pushToast(error instanceof Error ? error.message : 'No se pudo eliminar', 'error'),
  })

  if (details.length === 0) {
    return (
      <div className="rounded-3xl border border-white/5 bg-card p-6 text-center shadow-xl shadow-black/20">
        <p className="text-sm text-gray-400">
          La cuenta está vacía. Agrega productos desde la pestaña Productos.
        </p>
      </div>
    )
  }

  const pending = details.filter((detail) => detail.status === 'pending')
  const sent = details.filter((detail) => detail.status === 'sent')

  const renderRows = (items: OrderDetail[]) =>
    items.map((detail) => (
      <DetailRow
        key={detail.id}
        detail={detail}
        isCombo={isCombo(detail.product_id)}
        updating={updateMutation.isPending && updateMutation.variables?.detailId === detail.id}
        removing={removeMutation.isPending && removeMutation.variables === detail.id}
        onUpdate={(detailId, quantity) => updateMutation.mutate({ detailId, quantity })}
        onRemove={(detailId) => removeMutation.mutate(detailId)}
      />
    ))

  return (
    <div className="space-y-4">
      {pending.length > 0 && (
        <section className="rounded-3xl border border-white/5 bg-card px-4 shadow-xl shadow-black/20">
          <header className="border-b border-white/5 py-3">
            <h2 className="text-[11px] font-semibold uppercase tracking-wider text-gray-500">
              Pendientes de enviar
            </h2>
          </header>
          <ul className="divide-y divide-white/5">{renderRows(pending)}</ul>
        </section>
      )}

      {sent.length > 0 && (
        <section className="rounded-3xl border border-white/5 bg-card px-4 shadow-xl shadow-black/20">
          <header className="border-b border-white/5 py-3">
            <h2 className="text-[11px] font-semibold uppercase tracking-wider text-gray-500">
              Enviados a preparación
            </h2>
          </header>
          <ul className="divide-y divide-white/5">{renderRows(sent)}</ul>
        </section>
      )}
    </div>
  )
}
