import { apiFetch } from './client'
import type { RestaurantTable, Waiter } from './types'

interface TablesResponse {
  status: string
  data: RestaurantTable[]
}

interface WaitersResponse {
  status: string
  data: Waiter[]
}

interface OrderResponse {
  status: string
  order: { id: number }
}

interface ReleaseResponse {
  status: string
  message: string
  table: { id: number; name: string; status: string }
}

export function fetchTables(branchId: number) {
  return apiFetch<TablesResponse>(`/tables?branch_id=${branchId}`)
}

export function fetchWaiters(branchId: number) {
  return apiFetch<WaitersResponse>(`/waiters?branch_id=${branchId}`)
}

export function getOrCreateOrder(tableId: number, branchId: number, waiterId?: number) {
  return apiFetch<OrderResponse>('/orders/get-or-create', {
    method: 'POST',
    body: JSON.stringify({
      table_id: tableId,
      branch_id: branchId,
      ...(waiterId ? { waiter_id: waiterId } : {}),
    }),
  })
}

export function releaseTable(tableId: number) {
  return apiFetch<ReleaseResponse>(`/tables/${tableId}/release`, { method: 'PUT' })
}
