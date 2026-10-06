import { describe, expect, it } from 'vitest'
import { normalizeServiceUuid, parseServiceList } from './service-list'

describe('normalizeServiceUuid', () => {
  it('expande un UUID corto de 16 bits', () => {
    expect(normalizeServiceUuid('ffe0')).toBe('0000ffe0-0000-1000-8000-00805f9b34fb')
  })

  it('acepta y normaliza un UUID completo', () => {
    expect(normalizeServiceUuid('49535343-FE7D-4AE5-8FA9-9FAFD205E455')).toBe(
      '49535343-fe7d-4ae5-8fa9-9fafd205e455',
    )
  })

  it('rechaza valores inválidos', () => {
    expect(normalizeServiceUuid('no-es-uuid')).toBeNull()
    expect(normalizeServiceUuid('')).toBeNull()
  })
})

describe('parseServiceList', () => {
  it('separa, normaliza y quita duplicados', () => {
    expect(parseServiceList('ffe0, FF00;ffe0 0000fff0')).toEqual([
      '0000ffe0-0000-1000-8000-00805f9b34fb',
      '0000ff00-0000-1000-8000-00805f9b34fb',
      '0000fff0-0000-1000-8000-00805f9b34fb',
    ])
  })

  it('ignora valores inválidos', () => {
    expect(parseServiceList('ffe0, hola')).toEqual(['0000ffe0-0000-1000-8000-00805f9b34fb'])
  })
})
