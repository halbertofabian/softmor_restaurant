const MONTHS_ES = [
  'Ene',
  'Feb',
  'Mar',
  'Abr',
  'May',
  'Jun',
  'Jul',
  'Ago',
  'Sep',
  'Oct',
  'Nov',
  'Dic',
]

export function formatMoney(
  value: number | string | null | undefined,
  decimals = 2,
): string {
  const parsed = Number(value ?? 0)
  const amount = Number.isFinite(parsed) ? parsed : 0

  return `$${amount.toLocaleString('en-US', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  })}`
}

export function formatDateTime(value: string | Date | null | undefined): string {
  if (!value) {
    return '—'
  }

  const date = typeof value === 'string' ? new Date(value) : value

  if (Number.isNaN(date.getTime())) {
    return '—'
  }

  const day = String(date.getDate()).padStart(2, '0')
  const month = MONTHS_ES[date.getMonth()]
  const year = date.getFullYear()
  const minutes = String(date.getMinutes()).padStart(2, '0')
  const meridiem = date.getHours() >= 12 ? 'PM' : 'AM'

  let hours = date.getHours() % 12
  if (hours === 0) {
    hours = 12
  }

  return `${day}/${month}/${year} ${String(hours).padStart(2, '0')}:${minutes} ${meridiem}`
}
