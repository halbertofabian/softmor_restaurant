import { apiFetch } from './client'
import type { DashboardStats, WaiterDashboardStats } from './types'

export function fetchDashboard(branchId: number) {
  return apiFetch<{ status: string; data: DashboardStats }>(
    `/dashboard?branch_id=${branchId}`,
  )
}

export function fetchWaiterDashboard(branchId: number) {
  return apiFetch<{ status: string; data: WaiterDashboardStats }>(
    `/dashboard/waiter?branch_id=${branchId}`,
  )
}
