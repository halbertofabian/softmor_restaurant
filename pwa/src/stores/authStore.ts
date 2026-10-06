import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import type { AuthUser, Branch, MeResponse, Permissions } from '../lib/api/types'

interface SessionPayload {
  token: string
  user: AuthUser
  role: string
  tenantId: string
  branches: Branch[]
}

interface AuthState {
  token: string | null
  user: AuthUser | null
  role: string | null
  tenantId: string | null
  permissions: Permissions | null
  branches: Branch[]
  selectedBranchId: number | null
  setSession: (session: SessionPayload) => void
  applyMe: (me: MeResponse) => void
  selectBranch: (branchId: number) => void
  clearSession: () => void
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      token: null,
      user: null,
      role: null,
      tenantId: null,
      permissions: null,
      branches: [],
      selectedBranchId: null,
      setSession: ({ token, user, role, tenantId, branches }) =>
        set({ token, user, role, tenantId, branches }),
      applyMe: (me) =>
        set({
          user: me.user,
          role: me.role,
          tenantId: me.tenant_id,
          permissions: me.permissions,
          branches: me.branches,
        }),
      selectBranch: (branchId) => set({ selectedBranchId: branchId }),
      clearSession: () =>
        set({
          token: null,
          user: null,
          role: null,
          tenantId: null,
          permissions: null,
          branches: [],
        }),
    }),
    { name: 'gestionalfood.auth' },
  ),
)
