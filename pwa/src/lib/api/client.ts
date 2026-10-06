import { useAuthStore } from '../../stores/authStore'

const API_URL = (import.meta.env.VITE_API_URL ?? '/api').replace(/\/+$/, '')

export class ApiError extends Error {
  status: number
  body: unknown

  constructor(status: number, message: string, body?: unknown) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.body = body
  }
}

type UnauthorizedHandler = () => void

let unauthorizedHandler: UnauthorizedHandler | null = null

export function setUnauthorizedHandler(handler: UnauthorizedHandler) {
  unauthorizedHandler = handler
}

export async function apiFetch<T>(path: string, options: RequestInit = {}): Promise<T> {
  const token = useAuthStore.getState().token

  const headers = new Headers(options.headers)
  headers.set('Accept', 'application/json')

  if (options.body && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json')
  }

  if (token) {
    headers.set('Authorization', `Bearer ${token}`)
  }

  const response = await fetch(`${API_URL}${path}`, {
    ...options,
    headers,
  })

  const isJson = response.headers.get('content-type')?.includes('application/json') ?? false
  const data: unknown = isJson ? await response.json() : null

  if (!response.ok) {
    if (response.status === 401 && token) {
      useAuthStore.getState().clearSession()
      unauthorizedHandler?.()
    }

    const message =
      typeof data === 'object' && data !== null && 'message' in data
        ? String((data as { message: unknown }).message)
        : `Error ${response.status}`

    throw new ApiError(response.status, message, data)
  }

  return data as T
}
