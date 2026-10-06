import { apiFetch } from './client'

export interface PreparationArea {
  id: number
  name: string
  print_ticket: boolean
  sort_order: number
  status: boolean
  system_printer_name: string | null
}

interface PreparationAreasResponse {
  status: string
  data: PreparationArea[]
}

export function fetchPreparationAreas(branchId: number) {
  return apiFetch<PreparationAreasResponse>(`/preparation-areas?branch_id=${branchId}`)
}
