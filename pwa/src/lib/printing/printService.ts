import { useToastStore } from '../../stores/toastStore'
import { markPrinted } from '../api/orders'
import type { PreCheckPayload, PrintArea, PrintPayload } from '../api/types'
import { logPrint } from '../db/activity'
import type { LocalPrinter, PrintLogKind } from '../db/db'
import { getMappings, listPrinters, updatePrinter } from '../db/printers'
import {
  buildKitchenTicket,
  buildPreCheckTicket,
  buildTestTicket,
  formatTicketDate,
} from './encoder'
import { BridgeTransport, BluetoothTransport, pairBluetoothPrinter } from './transport'

export const PRE_CHECK_AREA_ID = 0

export interface AreaPrintOutcome {
  printed: { areaId: number; areaName: string; detailIds: number[] }[]
  missing: string[]
  failed: { areaName: string; error: string }[]
}

interface PrintContext {
  kind: PrintLogKind
  orderId: number | null
  tableName: string | null
}

export async function rePairPrinter(printer: LocalPrinter): Promise<LocalPrinter> {
  const paired = await pairBluetoothPrinter(printer.bluetoothServices ?? [])

  await updatePrinter(printer.id, {
    bluetoothDeviceId: paired.deviceId,
    bluetoothDeviceName: paired.deviceName,
    gatt: paired.gatt,
  })

  return {
    ...printer,
    bluetoothDeviceId: paired.deviceId,
    bluetoothDeviceName: paired.deviceName,
    gatt: paired.gatt,
  }
}

async function connectBluetooth(
  transport: BluetoothTransport,
  allowPairing: boolean,
): Promise<void> {
  const delays = [0, 1500, 3000]
  let lastError: unknown

  for (let attempt = 0; attempt < delays.length; attempt += 1) {
    if (delays[attempt] > 0) {
      await new Promise((resolve) => setTimeout(resolve, delays[attempt]))
    }

    try {
      await transport.connect({ allowPairing })
      return
    } catch (error) {
      const message = error instanceof Error ? error.message : ''

      // Si el usuario canceló el selector, no reintentar.
      if (/no se seleccionó|no device selected|cancel/i.test(message)) {
        throw error
      }

      // Reintento: la impresora pudo estar despertando o reconectando.
      lastError = error
    }
  }

  throw lastError
}

export async function printTestTicket(
  printer: LocalPrinter,
  options: { allowPairing?: boolean } = {},
): Promise<void> {
  if (printer.transport === 'bridge') {
    const transport = new BridgeTransport(printer)

    await transport.sendPayload({
      type: 'kitchen',
      table_name: 'Prueba',
      waiter_name: 'GestionalFood',
      date: formatTicketDate(new Date()),
      items: [{ quantity: 1, name: `PRUEBA · ${printer.alias}`, notes: '' }],
    })

    return
  }

  const transport = new BluetoothTransport(printer)

  try {
    await connectBluetooth(transport, options.allowPairing ?? false)
    await transport.writeBytes(buildTestTicket(printer))
  } finally {
    await transport.disconnect()
  }
}

async function sendAreaTicket(
  printer: LocalPrinter,
  area: PrintArea,
  payload: PrintPayload,
  allowPairing: boolean,
): Promise<void> {
  const copies = Math.max(1, printer.copies || 1)

  if (printer.transport === 'bridge') {
    const transport = new BridgeTransport(printer)

    await transport.sendPayload({
      type: 'kitchen',
      table_name: payload.table_name ?? '',
      waiter_name: payload.waiter_name ?? '',
      date: formatTicketDate(payload.generated_at),
      items: area.items.map((item) => ({
        quantity: item.quantity,
        name: item.name,
        notes: item.notes,
      })),
    })

    return
  }

  const transport = new BluetoothTransport(printer)

  try {
    await connectBluetooth(transport, allowPairing)

    for (let copy = 0; copy < copies; copy += 1) {
      await transport.writeBytes(buildKitchenTicket(printer, area, payload))
    }
  } finally {
    await transport.disconnect()
  }
}

export async function printPreCheck(
  tenantId: string,
  branchId: number,
  orderId: number,
  data: PreCheckPayload,
): Promise<void> {
  const [printers, mappings] = await Promise.all([
    listPrinters(),
    getMappings(tenantId, branchId),
  ])

  const mapping = mappings.find((item) => item.areaId === PRE_CHECK_AREA_ID)
  const printer = mapping ? printers.find((item) => item.id === mapping.printerId) : undefined

  if (!printer) {
    throw new Error('No hay una impresora configurada para la cuenta en este dispositivo.')
  }

  try {
    if (printer.transport === 'bridge') {
      const transport = new BridgeTransport(printer)

      await transport.sendPayload({
        type: 'pre_check',
        header: data.header,
        pre_check_disclaimer: data.disclaimer,
        branch_name: data.branch_name,
        ticket_id: data.ticket_number,
        date: formatTicketDate(data.generated_at),
        total: data.total,
        table_name: data.table_name ?? '',
        waiter_name: data.waiter_name ?? '',
        items: data.items.map((item) => ({
          quantity: item.quantity,
          name: item.name,
          price: item.price,
          notes: item.notes,
        })),
        tips_enabled: data.tips_enabled,
        tip_suggestions: data.tip_suggestions,
      })
    } else {
      const transport = new BluetoothTransport(printer)
      const copies = Math.max(1, printer.copies || 1)

      try {
        await connectBluetooth(transport, true)

        for (let copy = 0; copy < copies; copy += 1) {
          await transport.writeBytes(buildPreCheckTicket(printer, data))
        }
      } finally {
        await transport.disconnect()
      }
    }

    await logPrint({
      tenantId,
      branchId,
      orderId,
      tableName: data.table_name,
      areaId: PRE_CHECK_AREA_ID,
      areaName: 'Cuenta (pre-cuenta)',
      printerId: printer.id,
      printerAlias: printer.alias,
      kind: 'precheck',
      status: 'ok',
    })
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Error de impresión'

    await logPrint({
      tenantId,
      branchId,
      orderId,
      tableName: data.table_name,
      areaId: PRE_CHECK_AREA_ID,
      areaName: 'Cuenta (pre-cuenta)',
      printerId: printer.id,
      printerAlias: printer.alias,
      kind: 'precheck',
      status: 'error',
      error: message,
    })

    throw new Error(message)
  }
}

export async function printKitchenPayload(
  tenantId: string,
  branchId: number,
  payload: PrintPayload,
  options: { kind?: PrintLogKind; orderId?: number | null; allowPairing?: boolean } = {},
): Promise<AreaPrintOutcome> {
  const outcome: AreaPrintOutcome = { printed: [], missing: [], failed: [] }

  if (payload.areas.length === 0) {
    return outcome
  }

  const context: PrintContext = {
    kind: options.kind ?? 'send',
    orderId: options.orderId ?? null,
    tableName: payload.table_name,
  }

  const [printers, mappings] = await Promise.all([
    listPrinters(),
    getMappings(tenantId, branchId),
  ])

  const printerById = new Map(printers.map((printer) => [printer.id, printer]))
  const mappingByArea = new Map(mappings.map((mapping) => [mapping.areaId, mapping]))

  for (const area of payload.areas) {
    const mapping = mappingByArea.get(area.area_id)
    const printer = mapping ? printerById.get(mapping.printerId) : undefined

    if (!printer) {
      outcome.missing.push(area.area_name)
      continue
    }

    try {
      await sendAreaTicket(printer, area, payload, options.allowPairing ?? false)
      outcome.printed.push({
        areaId: area.area_id,
        areaName: area.area_name,
        detailIds: area.detail_ids,
      })

      await logPrint({
        tenantId,
        branchId,
        orderId: context.orderId,
        tableName: context.tableName,
        areaId: area.area_id,
        areaName: area.area_name,
        printerId: printer.id,
        printerAlias: printer.alias,
        kind: context.kind,
        status: 'ok',
      })
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Error de impresión'

      outcome.failed.push({ areaName: area.area_name, error: message })

      await logPrint({
        tenantId,
        branchId,
        orderId: context.orderId,
        tableName: context.tableName,
        areaId: area.area_id,
        areaName: area.area_name,
        printerId: printer.id,
        printerAlias: printer.alias,
        kind: context.kind,
        status: 'error',
        error: message,
      })
    }
  }

  return outcome
}

export async function printAndMark(
  tenantId: string,
  branchId: number,
  orderId: number,
  payload: PrintPayload,
  kind: PrintLogKind = 'send',
  options: { allowPairing?: boolean } = {},
): Promise<AreaPrintOutcome> {
  const outcome = await printKitchenPayload(tenantId, branchId, payload, {
    kind,
    orderId,
    allowPairing: options.allowPairing,
  })

  if (outcome.printed.length > 0) {
    const detailIds = outcome.printed.flatMap((area) => area.detailIds)

    try {
      await markPrinted(orderId, detailIds)
    } catch {
      // El marcado de impresión no debe romper el flujo.
    }
  }

  return outcome
}

export function reportPrintOutcome(outcome: AreaPrintOutcome): void {
  const push = useToastStore.getState().push

  if (outcome.printed.length > 0) {
    push(`Impreso: ${outcome.printed.map((area) => area.areaName).join(', ')}.`, 'success')
  }

  if (outcome.missing.length > 0) {
    const areas = outcome.missing.join(', ')
    const message =
      outcome.missing.length === 1
        ? `La comanda fue guardada, pero ${areas} no tiene una impresora configurada en este dispositivo.`
        : `La comanda fue guardada, pero ${areas} no tienen una impresora configurada en este dispositivo.`

    push(message, 'error')
  }

  for (const failure of outcome.failed) {
    push(`No se pudo imprimir ${failure.areaName}: ${failure.error}`, 'error')
  }
}
