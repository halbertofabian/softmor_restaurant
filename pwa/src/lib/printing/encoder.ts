import ReceiptPrinterEncoder from '@point-of-sale/receipt-printer-encoder'
import type { PreCheckPayload, PrintArea, PrintPayload } from '../api/types'
import type { LocalPrinter } from '../db/db'
import { formatMoney } from '../format'

function columnsFor(printer: LocalPrinter): number {
  return printer.width === 80 ? 48 : 32
}

function separator(printer: LocalPrinter): string {
  return '-'.repeat(columnsFor(printer))
}

function padLine(columns: number, left: string, right: string): string[] {
  const available = columns - right.length

  if (left.length <= available) {
    return [`${left}${' '.repeat(available - left.length)}${right}`]
  }

  return [left, right.padStart(columns)]
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

export function buildPreCheckTicket(printer: LocalPrinter, data: PreCheckPayload): Uint8Array {
  const columns = columnsFor(printer)
  const encoder = createEncoder(printer)

  encoder
    .initialize()
    .codepage('cp858')
    .align('center')
    .bold(true)
    .size(1, 2)
    .line(data.header)
    .size(1, 1)
    .bold(false)
    .line(data.branch_name)
    .align('left')

  if (data.table_name) {
    encoder.line(`Mesa: ${data.table_name}${data.table_zone ? ` (${data.table_zone})` : ''}`)
  }

  if (data.waiter_name) {
    encoder.line(`Mesero: ${data.waiter_name}`)
  }

  encoder
    .line(formatTicketDate(data.generated_at))
    .line(`Ticket #: ${data.ticket_number}`)
    .line(separator(printer))

  for (const item of data.items) {
    for (const line of padLine(columns, `${item.quantity}x ${item.name}`, formatMoney(item.line_total))) {
      encoder.line(line)
    }

    if (item.notes) {
      encoder.line(`   > ${item.notes}`)
    }
  }

  encoder.line(separator(printer))

  const totalLines = padLine(columns, 'TOTAL', formatMoney(data.total))
  encoder.bold(true)
  totalLines.forEach((line) => encoder.line(line))
  encoder.bold(false)

  if (data.tips_enabled && data.tip_suggestions.length > 0) {
    encoder.line(separator(printer)).line('PROPINA SUGERIDA')

    for (const tip of data.tip_suggestions) {
      encoder.line(`${tip.percent}%: ${formatMoney(tip.amount)}`)
    }
  }

  if (data.disclaimer) {
    encoder.align('center').newline(1).line(data.disclaimer).align('left')
  }

  if (data.footer_message) {
    encoder.line(data.footer_message)
  }

  encoder.newline(2)

  return encoder.cut('full').encode()
}
