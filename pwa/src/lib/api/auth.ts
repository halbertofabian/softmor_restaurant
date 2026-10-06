import { apiFetch } from './client'
import type { LoginResponse, MeResponse } from './types'

export interface LoginPayload {
  email: string
  password: string
  device_name?: string
}

export function login(payload: LoginPayload) {
  return apiFetch<LoginResponse>('/login', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function fetchMe() {
  return apiFetch<MeResponse>('/me')
}

export function logout() {
  return apiFetch<{ status: string }>('/logout', { method: 'POST' })
}
