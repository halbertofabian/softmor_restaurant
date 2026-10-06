import { useState } from 'react'
import { Button } from '../../components/ui/Button'
import { Sheet } from '../../components/ui/Sheet'
import { MinusIcon, PlusIcon } from '../../components/ui/icons'
import type { Product } from '../../lib/api/types'
import { formatMoney } from '../../lib/format'

export interface ProductSheetInput {
  quantity: number
  flavorId: number | null
  notes: string
}

interface ProductSheetProps {
  product: Product
  submitting: boolean
  onClose: () => void
  onSubmit: (input: ProductSheetInput) => void
}

export function ProductSheet({ product, submitting, onClose, onSubmit }: ProductSheetProps) {
  const [quantity, setQuantity] = useState(1)
  const [flavorId, setFlavorId] = useState<number | null>(null)
  const [notes, setNotes] = useState('')

  const flavor = product.flavors.find((item) => item.id === flavorId)
  const unitPrice = Number(product.price) + Number(flavor?.additional_price ?? 0)
  const hasFlavors = product.flavors.length > 0

  const chipClass = (isActive: boolean) =>
    `rounded-full border px-3 py-1.5 text-xs font-medium transition ${
      isActive
        ? 'border-primary bg-primary/15 text-primary'
        : 'border-white/10 text-gray-400 hover:text-white'
    }`

  return (
    <Sheet open title={product.name} onClose={onClose}>
      {product.description && <p className="mb-3 text-sm text-gray-400">{product.description}</p>}

      {product.type === 'combo' && product.combo_items.length > 0 && (
        <div className="mb-4 rounded-2xl border border-white/5 bg-white/5 p-3">
          <p className="text-[11px] font-semibold uppercase tracking-wider text-gray-500">
            Incluye
          </p>
          <ul className="mt-1.5 space-y-0.5 text-sm text-gray-300">
            {product.combo_items.map((item, index) => (
              <li key={`${item.product_id}-${index}`}>
                {item.quantity}× {item.name ?? 'Producto'}
                {item.default_flavor_name ? ` (${item.default_flavor_name})` : ''}
              </li>
            ))}
          </ul>
        </div>
      )}

      {hasFlavors && (
        <div className="mb-4">
          <p className="mb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">
            Sabor
          </p>
          <div className="flex flex-wrap gap-2">
            <button type="button" onClick={() => setFlavorId(null)} className={chipClass(flavorId === null)}>
              Sin sabor
            </button>
            {product.flavors.map((item) => (
              <button
                key={item.id}
                type="button"
                onClick={() => setFlavorId(item.id)}
                className={chipClass(flavorId === item.id)}
              >
                {item.name}
                {Number(item.additional_price) > 0 && ` +${formatMoney(item.additional_price)}`}
              </button>
            ))}
          </div>
        </div>
      )}

      <div className="mb-4 flex items-center justify-between rounded-2xl border border-white/5 bg-white/5 px-4 py-3">
        <span className="text-sm text-gray-300">Cantidad</span>
        <div className="flex items-center gap-3">
          <button
            type="button"
            onClick={() => setQuantity((value) => Math.max(1, value - 1))}
            className="rounded-lg border border-white/10 p-1.5 text-gray-300 transition hover:bg-white/5"
            aria-label="Menos"
          >
            <MinusIcon className="h-4 w-4" />
          </button>
          <span className="w-6 text-center text-sm font-semibold text-white">{quantity}</span>
          <button
            type="button"
            onClick={() => setQuantity((value) => Math.min(99, value + 1))}
            className="rounded-lg border border-white/10 p-1.5 text-gray-300 transition hover:bg-white/5"
            aria-label="Más"
          >
            <PlusIcon className="h-4 w-4" />
          </button>
        </div>
      </div>

      <div className="mb-5">
        <label
          htmlFor="product-notes"
          className="mb-2 block text-[11px] font-semibold uppercase tracking-wider text-gray-500"
        >
          Notas (opcional)
        </label>
        <input
          id="product-notes"
          value={notes}
          onChange={(event) => setNotes(event.target.value)}
          placeholder="Ej. sin cebolla, término medio…"
          maxLength={255}
          className="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-gray-600 focus:border-primary"
        />
      </div>

      <div className="flex items-center justify-between gap-3">
        <p className="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Subtotal</p>
        <p className="text-lg font-bold text-white">{formatMoney(unitPrice * quantity)}</p>
      </div>

      <Button
        onClick={() => onSubmit({ quantity, flavorId, notes: notes.trim() })}
        disabled={submitting}
        className="mt-4 w-full"
      >
        {submitting ? 'Agregando…' : 'Agregar a la cuenta'}
      </Button>
    </Sheet>
  )
}
