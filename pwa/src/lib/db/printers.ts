import { db, type AreaPrinterMapping, type LocalPrinter, type LocalSettings } from './db'

const SETTINGS_KEY = 'app'

export function mappingKey(tenantId: string, branchId: number, areaId: number): string {
  return `${tenantId}:${branchId}:${areaId}`
}

export function listPrinters(): Promise<LocalPrinter[]> {
  return db.printers.orderBy('alias').toArray()
}

export async function savePrinter(
  input: Omit<LocalPrinter, 'id' | 'createdAt'> & { id?: string },
): Promise<LocalPrinter> {
  const printer: LocalPrinter = {
    ...input,
    id: input.id ?? crypto.randomUUID(),
    createdAt: new Date().toISOString(),
  }

  await db.printers.put(printer)

  return printer
}

export function deletePrinter(printerId: string): Promise<void> {
  return db.transaction('rw', db.printers, db.areaPrinters, async () => {
    await db.printers.delete(printerId)
    await db.areaPrinters.where('printerId').equals(printerId).delete()
  })
}

export async function updatePrinter(
  printerId: string,
  changes: Partial<LocalPrinter>,
): Promise<void> {
  await db.printers.update(printerId, changes)
}

export function getMappings(tenantId: string, branchId: number): Promise<AreaPrinterMapping[]> {
  return db.areaPrinters.where('[tenantId+branchId]').equals([tenantId, branchId]).toArray()
}

export async function setAreaPrinter(input: {
  tenantId: string
  branchId: number
  areaId: number
  areaName: string
  printerId: string
}): Promise<void> {
  await db.areaPrinters.put({
    key: mappingKey(input.tenantId, input.branchId, input.areaId),
    tenantId: input.tenantId,
    branchId: input.branchId,
    areaId: input.areaId,
    areaName: input.areaName,
    printerId: input.printerId,
    updatedAt: new Date().toISOString(),
  })
}

export function clearAreaPrinter(
  tenantId: string,
  branchId: number,
  areaId: number,
): Promise<void> {
  return db.areaPrinters.delete(mappingKey(tenantId, branchId, areaId))
}

export function readSettings(): Promise<LocalSettings | undefined> {
  return db.settings.get(SETTINGS_KEY)
}

export async function ensureSettings(): Promise<LocalSettings> {
  const existing = await db.settings.get(SETTINGS_KEY)

  if (existing) {
    return existing
  }

  const created: LocalSettings = {
    key: SETTINGS_KEY,
    autoPrint: true,
    deviceUuid: crypto.randomUUID(),
  }

  await db.settings.put(created)

  return created
}

export async function getSettings(): Promise<LocalSettings> {
  return (await db.settings.get(SETTINGS_KEY)) ?? ensureSettings()
}

export function saveSettings(settings: LocalSettings): Promise<string> {
  return db.settings.put(settings)
}
