import { useEffect } from 'react'
import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuthStore } from '../stores/authStore'

export function RequireAuth() {
  const token = useAuthStore((state) => state.token)
  const location = useLocation()

  if (!token) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />
  }

  return <Outlet />
}

export function RequireBranch() {
  const selectedBranchId = useAuthStore((state) => state.selectedBranchId)
  const branches = useAuthStore((state) => state.branches)
  const selectBranch = useAuthStore((state) => state.selectBranch)

  const singleBranchId = branches.length === 1 ? branches[0].id : null
  const needsAutoSelect = singleBranchId !== null && selectedBranchId !== singleBranchId

  useEffect(() => {
    if (needsAutoSelect && singleBranchId !== null) {
      selectBranch(singleBranchId)
    }
  }, [needsAutoSelect, singleBranchId, selectBranch])

  if (needsAutoSelect) {
    return null
  }

  if (!selectedBranchId || (branches.length > 0 && !branches.some((branch) => branch.id === selectedBranchId))) {
    return <Navigate to="/select-branch" replace />
  }

  return <Outlet />
}
