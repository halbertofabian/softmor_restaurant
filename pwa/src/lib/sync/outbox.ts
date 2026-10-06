import { useToastStore } from '../../stores/toastStore'
import { ApiError } from '../api/client'
import { sendOrder } from '../api/orders'
import { enqueueOutbox, listOutbox, markOutboxAttempt, removeOutbox } from '../db/activity'
import { getSettings } from '../db/printers'

let flushing = false

export function isNetworkError(error: unknown): boolean {
  return !(error instanceof ApiError)
}

export async function queueOrderSend(input: {
  tenantId: string
  branchId: number
  orderId: number
  allowNegativeInventory: boolean
}): Promise<void> {
  const existing = (await listOutbox()).find((item) => item.orderId === input.orderId)

  if (existing) {
    return
  }

  await enqueueOutbox(input)
}

export async function flushOutbox(): Promise<number> {
  if (flushing || !navigator.onLine) {
    return 0
  }

  flushing = true
  let sent = 0

  try {
    const items = await listOutbox()

    for (const item of items) {
      try {
        const response = await sendOrder(item.orderId, item.allowNegativeInventory)
        await removeOutbox(item.id)
        sent += 1

        const push = useToastStore.getState().push
        push(`Comanda #${item.orderId} enviada al reconectar.`, 'success')

        const settings = await getSettings()

        if (settings.autoPrint && response.print.areas.length > 0) {
          const { printAndMark, reportPrintOutcome } = await import('../printing/printService')

          const outcome = await printAndMark(
            item.tenantId,
            item.branchId,
            item.orderId,
            response.print,
            'send',
          )
          reportPrintOutcome(outcome)
        }
      } catch (error) {
        if (error instanceof ApiError) {
          await removeOutbox(item.id)
          useToastStore
            .getState()
            .push(`No se pudo enviar la comanda #${item.orderId}: ${error.message}`, 'error')
        } else {
          await markOutboxAttempt(
            item.id,
            error instanceof Error ? error.message : 'Sin conexión',
          )
          break
        }
      }
    }
  } finally {
    flushing = false
  }

  return sent
}
