import { describe, expect, it } from 'vitest'
import { formatDateTime, formatMoney } from './format'

describe('formatMoney', () => {
  it('formatea números y strings', () => {
    expect(formatMoney(12.5)).toBe('$12.50')
    expect(formatMoney('30.00')).toBe('$30.00')
  })

  it('usa separador de miles igual que number_format de PHP', () => {
    expect(formatMoney(5655)).toBe('$5,655.00')
    expect(formatMoney(1870, 0)).toBe('$1,870')
  })

  it('trata nulos como cero', () => {
    expect(formatMoney(null)).toBe('$0.00')
    expect(formatMoney(undefined)).toBe('$0.00')
  })
})

describe('formatDateTime', () => {
  it('devuelve guion para valores vacíos o inválidos', () => {
    expect(formatDateTime(null)).toBe('—')
    expect(formatDateTime('nope')).toBe('—')
  })

  it('formatea una fecha ISO', () => {
    const formatted = formatDateTime('2026-10-05T14:30:00-05:00')
    expect(formatted).toMatch(/\d{2}\/\d{2},?\s+\d{2}:\d{2}/)
  })
})
