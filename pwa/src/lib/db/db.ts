import Dexie, { type EntityTable } from 'dexie'

export type PrinterTransportKind = 'bridge'

export interface LocalPrinter {
  id: string
  alias: string
  transport: PrinterTransportKind
  bridgeUrl?: string
  bridgePrinterName?: string
  agentToken?: string
  width: 58 | 80
  copies: number
  createdAt: string
}

export interface AgentLink {
  baseUrl: string
  token: string
  agentId: string
  name: string
}

export interface AreaPrinterMapping {
  key: string
  tenantId: string
  branchId: number
  areaId: number
  areaName: string
  printerId: string
  updatedAt: string
}

export interface LocalSettings {
  key: string
  autoPrint: boolean
  deviceUuid: string
  agent?: AgentLink
}

export type PrintLogKind = 'send' | 'reprint' | 'test' | 'precheck'
export type PrintLogStatus = 'ok' | 'error'

export interface PrintLog {
  id: string
  tenantId: string
  branchId: number
  orderId: number | null
  tableName: string | null
  areaId: number | null
  areaName: string
  printerId: string
  printerAlias: string
  kind: PrintLogKind
  status: PrintLogStatus
  error?: string
  createdAt: string
}

export interface OutboxItem {
  id: string
  tenantId: string
  branchId: number
  orderId: number
  allowNegativeInventory: boolean
  attempts: number
  lastError?: string
  createdAt: string
}

export const db = new Dexie('gestionalfood_pwa') as Dexie & {
  printers: EntityTable<LocalPrinter, 'id'>
  areaPrinters: EntityTable<AreaPrinterMapping, 'key'>
  settings: EntityTable<LocalSettings, 'key'>
  printLogs: EntityTable<PrintLog, 'id'>
  outbox: EntityTable<OutboxItem, 'id'>
}

db.version(1).stores({
  printers: 'id, alias, transport',
  areaPrinters: 'key, tenantId, branchId, printerId, [tenantId+branchId]',
  settings: 'key',
})

db.version(2).stores({
  printLogs: 'id, createdAt, orderId, kind',
  outbox: 'id, createdAt, orderId',
})
