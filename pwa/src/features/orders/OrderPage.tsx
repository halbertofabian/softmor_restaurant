import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { ConfirmDialog } from '../../components/ui/ConfirmDialog'
import { ChevronLeftIcon, PrinterIcon, SendIcon } from '../../components/ui/icons'
import { ApiError } from '../../lib/api/client'
import { addOrderItem, fetchOrder, sendOrder, updateOrderItem } from '../../lib/api/orders'
import { fetchProducts } from '../../lib/api/products'
import { releaseTable } from '../../lib/api/tables'
import type { InventoryWarningBody, PrintPayload, Product } from '../../lib/api/types'
import { getSettings } from '../../lib/db/printers'
import { formatMoney } from '../../lib/format'
import { printAndMark, reportPrintOutcome } from '../../lib/printing/printService'
import { isNetworkError, queueOrderSend } from '../../lib/sync/outbox'
import { useAuthStore } from '../../stores/authStore'
import { useToastStore } from '../../stores/toastStore'
import { ReprintSheet } from '../history/ReprintSheet'
import { OrderItemsList } from './OrderItemsList'
import { ProductCatalog } from './ProductCatalog'
import { ProductSheet, type ProductSheetInput } from './ProductSheet'

export function OrderPage() {
  const params = useParams()
  const orderId = Number(params.orderId)
  const navigate = useNavigate()
  const user = useAuthStore((state) => state.user)
  const role = useAuthStore((state) => state.role)
  const tenantId = useAuthStore((state) => state.tenantId)
  const branchId = useAuthStore((state) => state.selectedBranchId)
  const queryClient = useQueryClient()
  const pushToast = useToastStore((state) => state.push)

  const [tab, setTab] = useState<'order' | 'catalog'>('order')
  const [selectedProduct, setSelectedProduct] = useState<Product | null>(null)
  const [inventoryWarning, setInventoryWarning] = useState<InventoryWarningBody | null>(null)
  const [confirmRelease, setConfirmRelease] = useState(false)
  const [pendingPrint, setPendingPrint] = useState<PrintPayload | null>(null)
  const [printing, setPrinting] = useState(false)
  const [reprintOpen, setReprintOpen] = useState(false)

  const orderQuery = useQuery({
    queryKey: ['order', orderId],
    queryFn: () => fetchOrder(orderId),
    enabled: Number.isFinite(orderId),
  })

  const productsQuery = useQuery({
    queryKey: ['products', branchId],
    queryFn: () => fetchProducts(branchId as number),
    enabled: Boolean(branchId),
  })

  const order = orderQuery.data?.order

  const productsById = new Map<number, Product>()
  for (const category of productsQuery.data?.data ?? []) {
    for (const product of category.products) {
      productsById.set(product.id, product)
    }
  }

  const invalidateOrder = () => {
    queryClient.invalidateQueries({ queryKey: ['order', orderId] })
    queryClient.invalidateQueries({ queryKey: ['tables', branchId] })
  }

  const addMutation = useMutation({
    mutationFn: async ({ product, input }: { product: Product; input: ProductSheetInput }) => {
      const existing =
        product.type !== 'combo'
          ? order?.details.find(
              (detail) =>
                detail.status === 'pending' &&
                !detail.is_combo_component &&
                detail.product_id === product.id &&
                (detail.product_flavor_id ?? null) === input.flavorId &&
                (detail.notes ?? '') === input.notes,
            )
          : undefined

      if (existing) {
        return updateOrderItem(orderId, existing.id, {
          quantity: existing.quantity + input.quantity,
        })
      }

      return addOrderItem(orderId, {
        product_id: product.id,
        product_flavor_id: input.flavorId,
        quantity: input.quantity,
        notes: input.notes || null,
      })
    },
    onSuccess: () => {
      setSelectedProduct(null)
      invalidateOrder()
    },
    onError: (error) =>
      pushToast(error instanceof Error ? error.message : 'No se pudo agregar el producto', 'error'),
  })

  const sendMutation = useMutation({
    mutationFn: (allowNegativeInventory: boolean) => sendOrder(orderId, allowNegativeInventory),
    onSuccess: async (response) => {
      setInventoryWarning(null)
      invalidateOrder()
      pushToast(`${response.updated_count} ítem(s) enviados a preparación.`, 'success')

      const areas = response.print?.areas ?? []

      if (areas.length === 0) {
        return
      }

      const settings = await getSettings()

      if (settings.autoPrint) {
        await runPrint(response.print)
      } else {
        setPendingPrint(response.print)
      }
    },
    onError: async (error, allowNegativeInventory) => {
      if (error instanceof ApiError && error.status === 409) {
        setInventoryWarning(error.body as InventoryWarningBody)
        return
      }

      if (isNetworkError(error) && tenantId && branchId) {
        await queueOrderSend({ tenantId, branchId, orderId, allowNegativeInventory })
        pushToast('Sin conexión: la comanda se enviará automáticamente al reconectar.', 'info')
        return
      }

      pushToast(error instanceof Error ? error.message : 'No se pudo enviar la comanda', 'error')
    },
  })

  async function runPrint(payload: PrintPayload) {
    if (!tenantId || !branchId) {
      return
    }

    setPrinting(true)

    try {
      const outcome = await printAndMark(tenantId, branchId, orderId, payload, 'send')
      reportPrintOutcome(outcome)
    } finally {
      setPrinting(false)
    }
  }

  const releaseMutation = useMutation({
    mutationFn: () => releaseTable(order?.table_id as number),
    onSuccess: async () => {
      setConfirmRelease(false)
      await queryClient.invalidateQueries({ queryKey: ['tables', branchId] })
      pushToast('Mesa liberada.', 'success')
      navigate('/mesas')
    },
    onError: (error) =>
      pushToast(error instanceof Error ? error.message : 'No se pudo desocupar la mesa', 'error'),
  })

  if (orderQuery.isLoading) {
    return (
      <div className="space-y-4">
        <div className="h-40 animate-pulse rounded-3xl border border-white/5 bg-card" />
        <div className="h-12 animate-pulse rounded-2xl border border-white/5 bg-card" />
      </div>
    )
  }

  if (orderQuery.isError || !order) {
    return (
      <Card>
        <p className="text-sm text-gray-400">No se pudo cargar la comanda.</p>
        <div className="mt-3 flex gap-2">
          <Button variant="secondary" size="sm" onClick={() => orderQuery.refetch()}>
            Reintentar
          </Button>
          <Link
            to="/mesas"
            className="rounded-xl border border-white/10 px-3 py-1.5 text-xs font-medium text-gray-300 transition hover:bg-white/5"
          >
            Volver a mesas
          </Link>
        </div>
      </Card>
    )
  }

  const pendingCount = order.details.filter((detail) => detail.status === 'pending').length
  const canRelease =
    order.details.length === 0 && (role !== 'mesero' || order.user_id === user?.id)

  return (
    <div className="space-y-4 pb-32">
      <div className="flex items-center gap-3">
        <Link
          to="/mesas"
          className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/10 text-gray-300 transition hover:bg-white/5"
          aria-label="Volver a mesas"
        >
          <ChevronLeftIcon className="h-4 w-4" />
        </Link>

        <div className="min-w-0 flex-1">
          <h1 className="truncate text-lg font-bold text-white">
            {order.table?.name ?? `Comanda #${order.id}`}
          </h1>
          <p className="truncate text-xs text-gray-500">
            Comanda #{order.id}
            {order.table?.zone ? ` · ${order.table.zone}` : ''}
            {order.user?.name ? ` · Atiende ${order.user.name}` : ''}
          </p>
        </div>

        <button
          type="button"
          onClick={() => setReprintOpen(true)}
          className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/10 text-gray-300 transition hover:bg-white/5"
          aria-label="Reimprimir comanda"
        >
          <PrinterIcon className="h-4 w-4" />
        </button>

        <Badge variant={order.status === 'open' ? 'primary' : 'neutral'}>
          {order.status === 'open' ? 'Abierta' : order.status}
        </Badge>
      </div>

      <div className="grid grid-cols-2 gap-1 rounded-2xl border border-white/5 bg-card p-1">
        <button
          type="button"
          onClick={() => setTab('order')}
          className={`rounded-xl py-2 text-xs font-semibold transition ${
            tab === 'order' ? 'bg-primary text-black' : 'text-gray-400 hover:text-white'
          }`}
        >
          Cuenta ({order.details.length})
        </button>
        <button
          type="button"
          onClick={() => setTab('catalog')}
          className={`rounded-xl py-2 text-xs font-semibold transition ${
            tab === 'catalog' ? 'bg-primary text-black' : 'text-gray-400 hover:text-white'
          }`}
        >
          Productos
        </button>
      </div>

      {tab === 'order' ? (
        <OrderItemsList
          orderId={order.id}
          details={order.details}
          isCombo={(productId) => productsById.get(productId)?.type === 'combo'}
          onChanged={invalidateOrder}
        />
      ) : productsQuery.isLoading ? (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
          {Array.from({ length: 6 }).map((_, index) => (
            <div
              key={index}
              className="h-24 animate-pulse rounded-2xl border border-white/5 bg-card"
            />
          ))}
        </div>
      ) : productsQuery.isError ? (
        <Card>
          <p className="text-sm text-gray-400">No se pudo cargar el catálogo de productos.</p>
          <Button
            variant="secondary"
            size="sm"
            className="mt-3"
            onClick={() => productsQuery.refetch()}
          >
            Reintentar
          </Button>
        </Card>
      ) : (
        <ProductCatalog
          categories={productsQuery.data?.data ?? []}
          onSelect={(product) => setSelectedProduct(product)}
        />
      )}

      <div className="fixed inset-x-0 bottom-[calc(78px+env(safe-area-inset-bottom))] z-30 mx-auto w-full max-w-3xl px-4">
        <div className="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-card/95 p-3 shadow-2xl shadow-black/50 backdrop-blur">
          <div className="min-w-0">
            <p className="text-[10px] font-semibold uppercase tracking-wider text-gray-500">
              Total
            </p>
            <p className="text-lg font-bold leading-tight text-white">
              {formatMoney(order.total)}
            </p>
            <p className="text-[10px] text-gray-500">
              {pendingCount > 0
                ? `${pendingCount} pendiente(s)`
                : order.details.length > 0
                  ? 'Todo enviado'
                  : 'Sin productos'}
            </p>
          </div>

          <div className="flex shrink-0 items-center gap-2">
            {canRelease && (
              <button
                type="button"
                onClick={() => setConfirmRelease(true)}
                className="whitespace-nowrap rounded-xl px-2.5 py-2 text-[11px] font-medium text-gray-400 transition hover:bg-red-500/10 hover:text-red-300"
              >
                Desocupar
              </button>
            )}

            <Button
              onClick={() => sendMutation.mutate(false)}
              disabled={pendingCount === 0 || sendMutation.isPending || printing}
            >
              <SendIcon className="h-4 w-4" />
              {sendMutation.isPending ? 'Enviando…' : printing ? 'Imprimiendo…' : 'Enviar'}
            </Button>
          </div>
        </div>
      </div>

      {selectedProduct && (
        <ProductSheet
          key={selectedProduct.id}
          product={selectedProduct}
          submitting={addMutation.isPending}
          onClose={() => setSelectedProduct(null)}
          onSubmit={(input) => addMutation.mutate({ product: selectedProduct, input })}
        />
      )}

      {reprintOpen && (
        <ReprintSheet
          orderId={order.id}
          title={order.table?.name ?? `Comanda #${order.id}`}
          onClose={() => setReprintOpen(false)}
        />
      )}

      <ConfirmDialog
        open={inventoryWarning !== null}
        title="Existencia insuficiente"
        message={
          <>
            <p>{inventoryWarning?.message}</p>
            {inventoryWarning && inventoryWarning.shortages.length > 0 && (
              <ul className="mt-2 list-inside list-disc space-y-0.5 text-xs">
                {inventoryWarning.shortages.map((shortage) => (
                  <li key={shortage.inventory_item_id}>
                    {shortage.name}: disponible {shortage.available}, requerido {shortage.required}
                  </li>
                ))}
              </ul>
            )}
            <p className="mt-2 text-xs">¿Enviar de todos modos y permitir stock negativo?</p>
          </>
        }
        confirmLabel="Enviar de todos modos"
        loading={sendMutation.isPending}
        onCancel={() => setInventoryWarning(null)}
        onConfirm={() => sendMutation.mutate(true)}
      />

      <ConfirmDialog
        open={confirmRelease}
        title="Desocupar mesa"
        message="Se cerrará la comanda y la mesa quedará libre. ¿Deseas continuar?"
        confirmLabel="Desocupar"
        loading={releaseMutation.isPending}
        onCancel={() => setConfirmRelease(false)}
        onConfirm={() => releaseMutation.mutate()}
      />

      <ConfirmDialog
        open={pendingPrint !== null}
        title="Imprimir comandas"
        message={
          <>
            <p>La comanda se guardó. ¿Enviar los tickets a las impresoras de este dispositivo?</p>
            {pendingPrint && pendingPrint.areas.length > 0 && (
              <ul className="mt-2 list-inside list-disc space-y-0.5 text-xs">
                {pendingPrint.areas.map((area) => (
                  <li key={area.area_id}>{area.area_name}</li>
                ))}
              </ul>
            )}
          </>
        }
        confirmLabel="Imprimir"
        loading={printing}
        onCancel={() => setPendingPrint(null)}
        onConfirm={() => {
          if (pendingPrint) {
            void runPrint(pendingPrint).finally(() => setPendingPrint(null))
          }
        }}
      />
    </div>
  )
}
