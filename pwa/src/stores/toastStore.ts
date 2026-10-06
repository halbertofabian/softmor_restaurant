import { create } from 'zustand'

export type ToastTone = 'success' | 'error' | 'info'

export interface Toast {
  id: number
  message: string
  tone: ToastTone
}

interface ToastState {
  toasts: Toast[]
  push: (message: string, tone?: ToastTone) => void
  dismiss: (id: number) => void
}

let nextId = 1

export const useToastStore = create<ToastState>((set) => ({
  toasts: [],
  push: (message, tone = 'info') => {
    const id = nextId++
    set((state) => ({ toasts: [...state.toasts, { id, message, tone }] }))

    setTimeout(() => {
      set((state) => ({ toasts: state.toasts.filter((toast) => toast.id !== id) }))
    }, 4000)
  },
  dismiss: (id) => set((state) => ({ toasts: state.toasts.filter((toast) => toast.id !== id) })),
}))
