import { db, type OutboxItem, type PrintLog } from './db'

export function logPrint(entry: Omit<PrintLog, 'id' | 'createdAt'>): Promise<string> {
  return db.printLogs.put({
    ...entry,
    id: crypto.randomUUID(),
    createdAt: new Date().toISOString(),
  })
}

export function listRecentPrintLogs(limit = 8): Promise<PrintLog[]> {
  return db.printLogs.orderBy('createdAt').reverse().limit(limit).toArray()
}

export function clearPrintLogs(): Promise<void> {
  return db.printLogs.clear()
}

export function enqueueOutbox(
  entry: Omit<OutboxItem, 'id' | 'createdAt' | 'attempts'>,
): Promise<string> {
  return db.outbox.put({
    ...entry,
    id: crypto.randomUUID(),
    attempts: 0,
    createdAt: new Date().toISOString(),
  })
}

export function listOutbox(): Promise<OutboxItem[]> {
  return db.outbox.orderBy('createdAt').toArray()
}

export function countOutbox(): Promise<number> {
  return db.outbox.count()
}

export function removeOutbox(id: string): Promise<void> {
  return db.outbox.delete(id)
}

export function markOutboxAttempt(id: string, error: string): Promise<number> {
  return db.outbox
    .where('id')
    .equals(id)
    .modify((item) => {
      item.attempts += 1
      item.lastError = error
    })
}
