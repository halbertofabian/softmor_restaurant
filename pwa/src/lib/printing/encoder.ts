import ReceiptPrinterEncoder from '@point-of-sale/receipt-printer-encoder'
import type { PrintArea, PrintPayload } from '../api/types'
import type { LocalPrinter } from '../db/db'

function columnsFor(printer: LocalPrinter): number {
  return printer.width === 80 ? 48 : 32
}

function separator(printer: LocalPrinter): string {
  return '-'.repeat(columnsFor(printer))
}

function createEncoder(printer: LocalPrinter) {
  return new ReceiptPrinterEncoder({
    language: 'esc-pos',
    columns: columnsFor(printer),
  })
}

export function formatTicketDate(value: string | Date): string {
  const date = typeof value === 'string' ? new Date(value) : value

  if (Number.isNaN(date.getTime())) {
    return new Date().toLocaleString('es-MX', { dateStyle: 'short', timeStyle: 'short' })
  }

  const pad = (part: number) => String(part).padStart(2, '0')

  return `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()} ${pad(date.getHours())}:${pad(date.getMinutes())}`
}

export function buildTestTicket(printer: LocalPrinter): Uint8Array {
  const encoder = createEncoder(printer)

  encoder
    .initialize()
    .codepage('cp858')
    .align('center')
    .bold(true)
    .size(2, 2)
    .line('PRUEBA')
    .size(1, 1)
    .bold(false)
    .line('Impresion local GestionalFood')
    .align('left')
    .line(separator(printer))
    .line(`Impresora: ${printer.alias}`)
    .line(`Transporte: ${printer.transport === 'bluetooth' ? 'Bluetooth' : 'Bridge local'}`)
    .line(`Ancho: ${printer.width}mm`)
    .line(`Fecha: ${formatTicketDate(new Date())}`)
    .line(separator(printer))
    .newline(2)

  return encoder.cut('full').encode()
}

export function buildKitchenTicket(
  printer: LocalPrinter,
  area: PrintArea,
  payload: PrintPayload,
): Uint8Array {
  const encoder = createEncoder(printer)

  encoder
    .initialize()
    .codepage('cp858')
    .align('center')
    .bold(true)
    .size(1, 2)
    .line(area.area_name.toUpperCase())
    .size(1, 1)
    .bold(false)

  if (payload.table_name) {
    encoder.line(`Mesa: ${payload.table_name}`)
  }

  if (payload.waiter_name) {
    encoder.line(`Mesero: ${payload.waiter_name}`)
  }

  encoder.line(formatTicketDate(payload.generated_at)).align('left').line(separator(printer))

  for (const item of area.items) {
    encoder.bold(true).line(`${item.quantity}x ${item.name}`).bold(false)

    if (item.notes) {
      encoder.line(`   > ${item.notes}`)
    }
  }

  encoder.line(separator(printer)).newline(1)

  return encoder.cut('full').encode()
}
