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

  it('formatea con mes en español y hora de 12 horas', () => {
    expect(formatDateTime(new Date(2026, 9, 7, 16, 21))).toBe('07/Oct/2026 04:21 PM')
    expect(formatDateTime(new Date(2026, 9, 7, 0, 5))).toBe('07/Oct/2026 12:05 AM')
    expect(formatDateTime(new Date(2026, 9, 7, 12, 0))).toBe('07/Oct/2026 12:00 PM')
  })
})
