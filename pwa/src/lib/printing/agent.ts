import type { AgentLink, LocalPrinter } from '../db/db'
import {
  deletePrinter,
  getSettings,
  listPrinters,
  savePrinter,
  saveSettings,
  updatePrinter,
} from '../db/printers'

const PAIRING_PARAM = 'gf_agent'

export interface AgentDevice {
  name: string
  address: string
}

export interface AgentPrinter {
  name: string
  address: string
  connected: boolean
}

export interface AgentStatus {
  version: string
  agentId: string
  printers: AgentPrinter[]
  devices: AgentDevice[]
}

interface PairingPayload {
  v?: unknown
  port?: unknown
  token?: unknown
  id?: unknown
  name?: unknown
}

export function agentApkUrl(): string {
  return `${import.meta.env.BASE_URL}GestionalFoodPrinter.apk`
}

function decodeBase64Url(value: string): string {
  const normalized = value.replace(/-/g, '+').replace(/_/g, '/')
  const padded = normalized + '='.repeat((4 - (normalized.length % 4)) % 4)

  return atob(padded)
}

export async function processAgentPairingFromUrl(): Promise<AgentLink | null> {
  if (typeof window === 'undefined') {
    return null
  }

  const params = new URLSearchParams(window.location.search)
  const raw = params.get(PAIRING_PARAM)

  if (!raw) {
    return null
  }

  params.delete(PAIRING_PARAM)
  const query = params.toString()
  window.history.replaceState(
    {},
    '',
    `${window.location.pathname}${query ? `?${query}` : ''}${window.location.hash}`,
  )

  try {
    const payload = JSON.parse(decodeBase64Url(raw)) as PairingPayload
    const port = Number(payload.port)
    const token = typeof payload.token === 'string' ? payload.token : ''

    if (!token || !Number.isFinite(port) || port <= 0) {
      return null
    }

    const link: AgentLink = {
      baseUrl: `http://127.0.0.1:${port}`,
      token,
      agentId: typeof payload.id === 'string' ? payload.id : '',
      name: typeof payload.name === 'string' ? payload.name : 'GestionalFood Printer',
    }

    await saveAgentLink(link)

    const status = await fetchAgentStatus(link)

    if (status) {
      await syncAgentPrinters(link, status.printers)
    }

    return link
  } catch {
    return null
  }
}

export async function saveAgentLink(link: AgentLink): Promise<void> {
  const settings = await getSettings()

  await saveSettings({ ...settings, agent: link })
}

export async function clearAgentLink(): Promise<void> {
  const settings = await getSettings()
  const link = settings.agent

  if (link) {
    const printers = (await listPrinters()).filter((printer) => printer.agentToken === link.token)

    for (const printer of printers) {
      await deletePrinter(printer.id)
    }
  }

  const next = { ...settings }
  delete next.agent

  await saveSettings(next)
}

export async function syncAgentPrinters(
  link: AgentLink,
  printers: AgentPrinter[],
): Promise<LocalPrinter[]> {
  const local = (await listPrinters()).filter((printer) => printer.agentToken === link.token)
  const wanted = new Map(printers.map((printer) => [printer.name, printer]))

  for (const printer of local) {
    if (!printer.bridgePrinterName || !wanted.has(printer.bridgePrinterName)) {
      await deletePrinter(printer.id)
    }
  }

  const result: LocalPrinter[] = []

  for (const target of printers) {
    const existing = local.find((printer) => printer.bridgePrinterName === target.name)

    if (existing) {
      await updatePrinter(existing.id, {
        alias: target.name,
        bridgeUrl: link.baseUrl,
      })

      result.push(existing)
      continue
    }

    const created = await savePrinter({
      alias: target.name,
      transport: 'bridge',
      bridgeUrl: link.baseUrl,
      bridgePrinterName: target.name,
      agentToken: link.token,
      width: 58,
      copies: 1,
    })

    result.push(created)
  }

  return result
}

async function agentFetch(
  path: string,
  link: AgentLink,
  init: RequestInit = {},
  timeoutMs = 3000,
): Promise<Response> {
  const controller = new AbortController()
  const timer = setTimeout(() => controller.abort(), timeoutMs)

  try {
    return await fetch(`${link.baseUrl}${path}`, {
      ...init,
      headers: {
        ...(init.headers ?? {}),
        'X-GF-Token': link.token,
      },
      signal: controller.signal,
    })
  } finally {
    clearTimeout(timer)
  }
}

export async function fetchAgentStatus(link: AgentLink): Promise<AgentStatus | null> {
  try {
    const response = await agentFetch('/api/agent/status', link)

    if (!response.ok) {
      return null
    }

    const data = (await response.json()) as {
      version?: unknown
      agent_id?: unknown
      printers?: unknown
      devices?: unknown
    }

    const devices: AgentDevice[] = Array.isArray(data.devices)
      ? data.devices
          .filter(
            (device): device is { name: unknown; address: unknown } =>
              typeof device === 'object' && device !== null,
          )
          .map((device) => ({
            name: String(device.name ?? device.address ?? ''),
            address: String(device.address ?? ''),
          }))
          .filter((device) => device.address.length > 0)
      : []

    const printers: AgentPrinter[] = Array.isArray(data.printers)
      ? data.printers
          .filter(
            (printer): printer is { name: unknown; address: unknown; connected: unknown } =>
              typeof printer === 'object' && printer !== null,
          )
          .map((printer) => ({
            name: String(printer.name ?? printer.address ?? ''),
            address: String(printer.address ?? ''),
            connected: printer.connected === true,
          }))
          .filter((printer) => printer.name.length > 0)
      : []

    return {
      version: typeof data.version === 'string' ? data.version : '',
      agentId: typeof data.agent_id === 'string' ? data.agent_id : '',
      printers,
      devices,
    }
  } catch {
    return null
  }
}
