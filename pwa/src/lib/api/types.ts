export interface Branch {
  id: number
  name: string
  address: string
  is_active: boolean
}

export interface AuthUser {
  id: number
  name: string
  email: string
  estado: string
  role: string
}

export interface Permissions {
  take_orders: boolean
  reprint: boolean
  manage_printers: boolean
}

export interface LoginResponse {
  status: string
  token: string
  user: AuthUser
  role: string
  tenant_id: string
  branches: Branch[]
}

export interface MeResponse {
  status: string
  user: AuthUser
  role: string
  tenant_id: string
  permissions: Permissions
  branches: Branch[]
}

export type TableStatus = 'free' | 'occupied' | 'reserved' | 'inactive'

export interface RestaurantTable {
  id: number
  name: string
  zone: string | null
  status: TableStatus
  has_active_order: boolean
  seats: number
  active_order_id: number | null
  active_order_waiter_id: number | null
  active_order_waiter_name: string | null
}

export interface Waiter {
  id: number
  name: string
}

export interface ProductFlavor {
  id: number
  name: string
  additional_price: number
}

export interface ComboItem {
  product_id: number
  name: string | null
  quantity: number
  default_flavor_name: string | null
}

export interface Product {
  id: number
  name: string
  type: 'dish' | 'drink' | 'finished' | 'extra' | 'combo'
  price: number | string
  image: string | null
  description: string | null
  controls_inventory: boolean
  stock: number
  preparation_area_id: number | null
  combo_items: ComboItem[]
  flavors: ProductFlavor[]
}

export interface ProductCategory {
  id: number
  name: string
  products: Product[]
}

export type OrderDetailStatus = 'pending' | 'sent' | 'served' | 'canceled'

export interface OrderDetail {
  id: number
  order_id: number
  product_id: number
  product_flavor_id: number | null
  product_name: string
  flavor_name: string | null
  price: number | string
  quantity: number
  notes: string | null
  status: OrderDetailStatus
  is_printed: boolean
  is_combo_component: boolean
  preparation_area_id: number | null
  preparation_area_name: string | null
}

export interface OrderTable {
  id: number
  name: string
  zone?: string | null
}

export interface Order {
  id: number
  table_id: number
  user_id: number | null
  status: string
  total: number | string
  notes: string | null
  table?: OrderTable
  user?: { id: number; name: string } | null
  details: OrderDetail[]
}

export interface PrintItem {
  detail_id: number
  quantity: number
  name: string
  notes: string
}

export interface PrintArea {
  area_id: number
  area_name: string
  print_ticket: boolean
  detail_ids: number[]
  items: PrintItem[]
}

export interface PrintPayload {
  generated_at: string
  table_name: string | null
  waiter_name: string | null
  areas: PrintArea[]
}

export interface SendOrderResponse {
  status: string
  message: string
  updated_count: number
  order: { id: number; status: string; total: number }
  print: PrintPayload
  inventory_warnings: unknown[]
}

export interface OrderSummary {
  id: number
  table_id: number
  table_name: string | null
  status: string
  total: number
  has_pending: boolean
  waiter_name: string | null
  created_at: string | null
  closed_at: string | null
}

export interface OrdersPageMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface InventoryShortage {
  inventory_item_id: number
  name: string
  unit: string
  available: number
  required: number
  resulting: number
}

export interface InventoryWarningBody {
  status: 'inventory_warning'
  message: string
  shortages: InventoryShortage[]
}
