export function normalizeServiceUuid(value: string): string | null {
  const trimmed = value.trim().toLowerCase()

  if (!trimmed) {
    return null
  }

  if (/^[0-9a-f]{4}$/.test(trimmed)) {
    return `0000${trimmed}-0000-1000-8000-00805f9b34fb`
  }

  if (/^[0-9a-f]{8}$/.test(trimmed)) {
    return `${trimmed}-0000-1000-8000-00805f9b34fb`
  }

  if (/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/.test(trimmed)) {
    return trimmed
  }

  return null
}

export function parseServiceList(value: string): string[] {
  const services = value
    .split(/[,\s;]+/)
    .map(normalizeServiceUuid)
    .filter((service): service is string => service !== null)

  return [...new Set(services)]
}
