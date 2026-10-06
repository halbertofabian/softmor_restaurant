import { apiFetch } from './client'
import type { ProductCategory } from './types'

interface ProductsResponse {
  status: string
  data: ProductCategory[]
}

export function fetchProducts(branchId: number) {
  return apiFetch<ProductsResponse>(`/products?branch_id=${branchId}`)
}
