import { describe, expect, it } from 'vitest'
import type { PreCheckPayload } from '../api/types'
import type { LocalPrinter } from '../db/db'
import type { PrintArea, PrintPayload } from '../api/types'
import { buildKitchenTicket, buildPreCheckTicket, buildTestTicket, formatTicketDate } from './encoder'

const printer: LocalPrinter = {
  id: 'printer-1',
  alias: 'Cocina BT',
  transport: 'bluetooth',
  width: 58,
  copies: 1,
  createdAt: '2026-10-05T00:00:00.000Z',
}

const area: PrintArea = {
  area_id: 1,
  area_name: 'Cocina',
  print_ticket: true,
  detail_ids: [10, 11],
  items: [
    { detail_id: 10, quantity: 2, name: 'Hamburguesa', notes: 'sin cebolla' },
    { detail_id: 11, quantity: 1, name: 'Papas', notes: '' },
  ],
}

const payload: PrintPayload = {
  generated_at: '2026-10-05T14:30:00-05:00',
  table_name: 'M1',
  waiter_name: 'Sara',
  areas: [area],
}

describe('formatTicketDate', () => {
  it('formatea fecha y hora', () => {
    expect(formatTicketDate(new Date(2026, 9, 5, 14, 30))).toBe('05/10/2026 14:30')
  })
})

describe('buildTestTicket', () => {
  it('inicia con ESC @ y termina con corte', () => {
    const bytes = buildTestTicket(printer)
    expect(bytes[0]).toBe(27)
    expect(bytes[1]).toBe(64)
    expect(Array.from(bytes.slice(-5, -2))).toEqual([29, 86, 0])
  })
})

describe('buildKitchenTicket', () => {
  it('incluye separadores ASCII y los ítems', () => {
    const bytes = buildKitchenTicket(printer, area, payload)
    const text = new TextDecoder('latin1').decode(bytes)

    expect(text).toContain('COCINA')
    expect(text).toContain('Mesa: M1')
    expect(text).toContain('2x Hamburguesa')
    expect(text).toContain('sin cebolla')
    expect(text).toContain('-'.repeat(32))
  })

  it('usa 48 columnas en 80mm', () => {
    const wide: LocalPrinter = { ...printer, width: 80 }
    const text = new TextDecoder('latin1').decode(buildKitchenTicket(wide, area, payload))
    expect(text).toContain('-'.repeat(48))
  })
})

const preCheck: PreCheckPayload = {
  order_id: 5,
  ticket_number: 5,
  branch_name: 'El Marisquero',
  table_name: 'M1',
  table_zone: null,
  waiter_name: 'Sara',
  generated_at: '2026-10-05T14:30:00-05:00',
  header: 'CUENTA DE CONSUMO',
  disclaimer: 'COMPROBANTE NO FISCAL',
  footer_message: null,
  items: [
    {
      detail_id: 1,
      quantity: 2,
      name: 'Hamburguesa',
      notes: 'sin cebolla',
      price: 10,
      line_total: 20,
    },
  ],
  total: 20,
  tips_enabled: true,
  tip_suggestions: [{ percent: 10, amount: 2 }],
}

describe('buildPreCheckTicket', () => {
  it('arma la pre-cuenta con total y propinas', () => {
    const text = new TextDecoder('latin1').decode(buildPreCheckTicket(printer, preCheck))

    expect(text).toContain('CUENTA DE CONSUMO')
    expect(text).toContain('El Marisquero')
    expect(text).toContain('2x Hamburguesa')
    expect(text).toContain('sin cebolla')
    expect(text).toContain('TOTAL')
    expect(text).toContain('$20.00')
    expect(text).toContain('PROPINA SUGERIDA')
    expect(text).toContain('10%: $2.00')
    expect(text).toContain('COMPROBANTE NO FISCAL')
  })
})
