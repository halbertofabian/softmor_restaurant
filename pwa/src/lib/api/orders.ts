import { apiFetch } from './client'
import type {
  Order,
  OrderDetail,
  OrdersPageMeta,
  OrderSummary,
  PreCheckPayload,
  PrintPayload,
  SendOrderResponse,
} from './types'

interface OrderResponse {
  status: string
  order: Order
}

interface DetailResponse {
  status: string
  detail: OrderDetail
  order_total: number | string
}

export interface AddOrderItemPayload {
  product_id: number
  product_flavor_id?: number | null
  quantity: number
  notes?: string | null
}

export interface UpdateOrderItemPayload {
  quantity?: number
  notes?: string | null
}

export function fetchOrder(orderId: number) {
  return apiFetch<OrderResponse>(`/orders/${orderId}`)
}

export function addOrderItem(orderId: number, payload: AddOrderItemPayload) {
  return apiFetch<DetailResponse>(`/orders/${orderId}/items`, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function updateOrderItem(orderId: number, detailId: number, payload: UpdateOrderItemPayload) {
  return apiFetch<DetailResponse>(`/orders/${orderId}/items/${detailId}`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  })
}

export function removeOrderItem(orderId: number, detailId: number) {
  return apiFetch<{ status: string; order_total: number | string }>(
    `/orders/${orderId}/items/${detailId}`,
    { method: 'DELETE' },
  )
}

export function sendOrder(orderId: number, allowNegativeInventory = false) {
  return apiFetch<SendOrderResponse>(`/orders/${orderId}/send`, {
    method: 'POST',
    body: JSON.stringify({
      print_mode: 'client',
      allow_negative_inventory: allowNegativeInventory,
    }),
  })
}

export function markPrinted(orderId: number, detailIds: number[]) {
  return apiFetch<{ status: string; updated: number }>(`/orders/${orderId}/mark-printed`, {
    method: 'POST',
    body: JSON.stringify({ detail_ids: detailIds }),
  })
}

export interface FetchOrdersParams {
  branchId: number
  status?: string
  tableId?: number
  page?: number
  perPage?: number
}

export function fetchOrders(params: FetchOrdersParams) {
  const query = new URLSearchParams({ branch_id: String(params.branchId) })

  if (params.status) {
    query.set('status', params.status)
  }

  if (params.tableId) {
    query.set('table_id', String(params.tableId))
  }

  query.set('page', String(params.page ?? 1))
  query.set('per_page', String(params.perPage ?? 20))

  return apiFetch<{ status: string; data: OrderSummary[]; meta: OrdersPageMeta }>(
    `/orders?${query.toString()}`,
  )
}

export function fetchOrderPrintPayload(orderId: number, areaId?: number) {
  const suffix = areaId ? `?area_id=${areaId}` : ''

  return apiFetch<{ status: string; print: PrintPayload }>(
    `/orders/${orderId}/print-payload${suffix}`,
  )
}

export function fetchPreCheck(orderId: number) {
  return apiFetch<{ status: string; pre_check: PreCheckPayload }>(
    `/orders/${orderId}/pre-check`,
  )
}
