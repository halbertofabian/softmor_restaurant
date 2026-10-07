import type { LocalPrinter } from '../db/db'

export interface BridgePrintPayload {
  type: 'kitchen' | 'pre_check'
  printer_name?: string
  area_name?: string
  header?: string
  pre_check_disclaimer?: string
  branch_name?: string
  ticket_id?: number
  total?: number
  table_name?: string
  waiter_name?: string
  date?: string
  items: { quantity: number; name: string; notes?: string; price?: number }[]
  tips_enabled?: boolean
  tip_suggestions?: { percent: number; amount: number }[]
}

export interface PrinterTransport {
  sendPayload(payload: BridgePrintPayload): Promise<void>
}

export function normalizeBridgeUrl(url: string): string {
  return (url.trim() || 'http://127.0.0.1:8000').replace(/\/+$/, '')
}

export class BridgeTransport implements PrinterTransport {
  private readonly printer: LocalPrinter

  constructor(printer: LocalPrinter) {
    this.printer = printer
  }

  private baseUrl(): string {
    return normalizeBridgeUrl(this.printer.bridgeUrl ?? 'http://127.0.0.1:8000')
  }

  async sendPayload(payload: BridgePrintPayload): Promise<void> {
    let response: Response

    try {
      response = await fetch(`${this.baseUrl()}/api/printer/raw`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...(this.printer.agentToken ? { 'X-GF-Token': this.printer.agentToken } : {}),
        },
        body: JSON.stringify({
          ...payload,
          printer_name: this.printer.bridgePrinterName,
        }),
      })
    } catch {
      throw new Error('No se pudo conectar con GestionalFood Printer.')
    }

    if (!response.ok) {
      let message = 'GestionalFood Printer no pudo imprimir.'

      try {
        const data: unknown = await response.json()

        if (typeof data === 'object' && data !== null && 'message' in data) {
          message = String((data as { message: unknown }).message)
        }
      } catch {
        // Sin detalle del agente.
      }

      throw new Error(message)
    }
  }
}
