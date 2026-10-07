import { useQuery } from '@tanstack/react-query'
import { useLiveQuery } from 'dexie-react-hooks'
import { useEffect, useState } from 'react'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { HistoryIcon, PrinterIcon, RefreshIcon, StoreIcon } from '../../components/ui/icons'
import { fetchPreparationAreas, type PreparationArea } from '../../lib/api/preparation-areas'
import { clearPrintLogs, listRecentPrintLogs } from '../../lib/db/activity'
import {
  clearAreaPrinter,
  deletePrinter,
  ensureSettings,
  getMappings,
  listPrinters,
  readSettings,
  saveSettings,
  setAreaPrinter,
} from '../../lib/db/printers'
import type { LocalPrinter, PrintLogKind } from '../../lib/db/db'
import { formatDateTime } from '../../lib/format'
import {
  agentApkUrl,
  clearAgentLink,
  fetchAgentStatus,
  syncAgentPrinters,
  type AgentStatus,
} from '../../lib/printing/agent'
import { printTestTicket } from '../../lib/printing/printService'
import { useAuthStore } from '../../stores/authStore'
import { useToastStore } from '../../stores/toastStore'

const kindLabels: Record<PrintLogKind, string> = {
  send: 'Comanda',
  reprint: 'Reimpresión',
  test: 'Prueba',
  precheck: 'Pre-cuenta',
}

export function PrintersPage() {
  const tenantId = useAuthStore((state) => state.tenantId)
  const branchId = useAuthStore((state) => state.selectedBranchId)
  const branches = useAuthStore((state) => state.branches)
  const pushToast = useToastStore((state) => state.push)

  const [testingId, setTestingId] = useState<string | null>(null)
  const [agentStatus, setAgentStatus] = useState<AgentStatus | null>(null)
  const [agentChecking, setAgentChecking] = useState(false)

  const printers = useLiveQuery(() => listPrinters(), []) ?? []
  const mappings =
    useLiveQuery(
      () => (tenantId && branchId ? getMappings(tenantId, branchId) : Promise.resolve([])),
      [tenantId, branchId],
    ) ?? []
  const settings = useLiveQuery(() => readSettings(), [])
  const logs = useLiveQuery(() => listRecentPrintLogs(8), []) ?? []
  const agentLink = settings?.agent

  useEffect(() => {
    void ensureSettings()
  }, [])

  const areasQuery = useQuery({
    queryKey: ['preparation-areas', branchId],
    queryFn: () => fetchPreparationAreas(branchId as number),
    enabled: Boolean(branchId),
  })

  const areas = areasQuery.data?.data ?? []
  const branch = branches.find((item) => item.id === branchId)
  const mappingByArea = new Map(mappings.map((mapping) => [mapping.areaId, mapping]))
  const printerById = new Map(printers.map((printer) => [printer.id, printer]))

  const preCheckArea: PreparationArea = {
    id: 0,
    name: 'Cuenta (pre-cuenta)',
    print_ticket: true,
    sort_order: -1,
    status: true,
    system_printer_name: null,
  }

  const areaRows = [preCheckArea, ...areas]
  const agentToken = agentLink?.token

  useEffect(() => {
    const link = agentLink

    if (!link || !agentToken) {
      setAgentStatus(null)
      return
    }

    const timer = window.setTimeout(() => {
      void refreshAgent(link)
    }, 0)

    return () => window.clearTimeout(timer)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [agentToken])

  async function refreshAgent(link: NonNullable<typeof agentLink>) {
    setAgentChecking(true)

    try {
      const status = await fetchAgentStatus(link)
      setAgentStatus(status)

      if (status) {
        await syncAgentPrinters(link, status.printers)
      }
    } finally {
      setAgentChecking(false)
    }
  }

  async function handleAssign(area: PreparationArea, printerId: string) {
    if (!tenantId || !branchId) {
      return
    }

    if (!printerId) {
      await clearAreaPrinter(tenantId, branchId, area.id)
      return
    }

    await setAreaPrinter({
      tenantId,
      branchId,
      areaId: area.id,
      areaName: area.name,
      printerId,
    })
  }

  async function handleTest(printer: LocalPrinter) {
    setTestingId(printer.id)

    try {
      await printTestTicket(printer)
      pushToast(`Prueba enviada a ${printer.alias}.`, 'success')
    } catch (error) {
      pushToast(
        error instanceof Error ? error.message : 'No se pudo imprimir la prueba',
        'error',
      )
    } finally {
      setTestingId(null)
    }
  }

  async function handleUnlink() {
    try {
      await clearAgentLink()
      setAgentStatus(null)
      pushToast('GestionalFood Printer desvinculada.', 'info')
    } catch {
      pushToast('No se pudo desvincular GestionalFood Printer.', 'error')
    }
  }

  async function handleForget(printer: LocalPrinter) {
    await deletePrinter(printer.id)
    pushToast(`Impresora "${printer.alias}" olvidada en este dispositivo.`, 'success')
  }

  async function handleAutoPrint(next: boolean) {
    const current = settings ?? (await ensureSettings())

    await saveSettings({ ...current, autoPrint: next })
  }

  return (
    <div className="space-y-4">
      <Card>
        <div className="flex items-center gap-3">
          <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <PrinterIcon className="h-5 w-5" />
          </span>
          <div>
            <h1 className="text-lg font-bold text-white">Impresoras</h1>
            <p className="flex items-center gap-1 text-xs text-gray-500">
              <StoreIcon className="h-3 w-3" />
              {branch?.name} · solo este dispositivo
            </p>
          </div>
        </div>
      </Card>

      <Card title="GestionalFood Printer" icon={<PrinterIcon className="h-4 w-4" />}>
        {!agentLink ? (
          <div className="space-y-3">
            <p className="text-sm text-gray-400">
              Imprime por Bluetooth sin volver a emparejar. Instala el agente en este dispositivo y
              vincúlalo con un toque.
            </p>
            <a
              href={agentApkUrl()}
              download
              className="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-black shadow-lg shadow-primary/20 transition hover:bg-primary-dark"
            >
              Descargar agente (APK)
            </a>
            <p className="text-xs text-gray-500">
              Abre <span className="font-semibold text-white">GestionalFood Printer</span>, elige
              tus impresoras y toca{' '}
              <span className="font-semibold text-white">Vincular con GestionalFood</span>. La app
              se configura sola.
            </p>
          </div>
        ) : agentChecking ? (
          <p className="text-sm text-gray-400">Consultando GestionalFood Printer…</p>
        ) : !agentStatus ? (
          <div className="space-y-3">
            <p className="text-sm text-gray-400">
              GestionalFood Printer no responde. Ábrelo en este dispositivo o vuelve a instalarlo.
            </p>
            <a
              href={agentApkUrl()}
              download
              className="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-black shadow-lg shadow-primary/20 transition hover:bg-primary-dark"
            >
              Descargar agente (APK)
            </a>
            <p className="text-xs text-gray-500">
              Abre <span className="font-semibold text-white">GestionalFood Printer</span> y toca{' '}
              <span className="font-semibold text-white">Vincular con GestionalFood</span> para
              reconectar. Si ya no lo usarás en este dispositivo, desvincúlalo.
            </p>
            <div className="flex flex-wrap gap-2">
              <Button variant="secondary" size="sm" onClick={() => void refreshAgent(agentLink)}>
                Reintentar
              </Button>
              <Button variant="danger" size="sm" onClick={() => void handleUnlink()}>
                Desvincular
              </Button>
            </div>
          </div>
        ) : (
          <div className="space-y-4">
            {agentStatus.printers.length === 0 ? (
              <p className="text-sm text-gray-400">
                Aún no hay impresoras configuradas. Abre{' '}
                <span className="font-semibold text-white">GestionalFood Printer</span> y elige las
                impresoras emparejadas.
              </p>
            ) : (
              <ul className="divide-y divide-white/5">
                {agentStatus.printers.map((printer) => (
                  <li
                    key={printer.address}
                    className="flex items-center justify-between gap-3 py-2.5"
                  >
                    <div className="min-w-0">
                      <p className="truncate text-sm font-medium text-white">{printer.name}</p>
                      <p className="truncate text-[11px] text-gray-500">{printer.address}</p>
                    </div>
                    <Badge variant={printer.connected ? 'success' : 'muted'}>
                      {printer.connected ? 'En línea' : 'Desconectada'}
                    </Badge>
                  </li>
                ))}
              </ul>
            )}

            <div className="flex flex-wrap items-center gap-3">
              <Button variant="secondary" size="sm" onClick={() => void refreshAgent(agentLink)}>
                Actualizar estado
              </Button>
              <Button variant="danger" size="sm" onClick={() => void handleUnlink()}>
                Desvincular
              </Button>
              {agentStatus.version && (
                <span className="text-[11px] text-gray-500">
                  GestionalFood Printer v{agentStatus.version}
                </span>
              )}
            </div>
          </div>
        )}
      </Card>

      <Card title="Preferencias" icon={<PrinterIcon className="h-4 w-4" />}>
        <div className="flex items-center justify-between gap-4">
          <div>
            <p className="text-sm text-gray-300">Imprimir automáticamente al enviar</p>
            <p className="mt-0.5 text-xs text-gray-500">
              Si lo desactivas, podrás elegir imprimir después de guardar la comanda.
            </p>
          </div>

          <button
            type="button"
            role="switch"
            aria-checked={settings?.autoPrint ?? true}
            onClick={() => handleAutoPrint(!(settings?.autoPrint ?? true))}
            className={`relative h-6 w-11 shrink-0 rounded-full transition ${
              (settings?.autoPrint ?? true) ? 'bg-primary' : 'bg-white/10'
            }`}
          >
            <span
              className={`absolute top-0.5 h-5 w-5 rounded-full bg-white transition-all ${
                (settings?.autoPrint ?? true) ? 'left-[22px]' : 'left-0.5'
              }`}
            />
          </button>
        </div>
      </Card>

      <Card
        title="Impresoras de este dispositivo"
        icon={<PrinterIcon className="h-4 w-4" />}
        action={<Badge variant="muted">{printers.length}</Badge>}
      >
        {printers.length === 0 ? (
          <p className="text-sm text-gray-400">
            Aún no hay impresoras configuradas. Instala GestionalFood Printer y vincúlalo para
            comenzar a imprimir.
          </p>
        ) : (
          <ul className="divide-y divide-white/5">
            {printers.map((printer) => (
              <li key={printer.id} className="flex flex-wrap items-center justify-between gap-3 py-3">
                <div className="min-w-0">
                  <p className="truncate text-sm font-medium text-white">{printer.alias}</p>
                  <p className="truncate text-[11px] text-gray-500">
                    GestionalFood Printer · {printer.copies} copia(s)
                  </p>
                </div>

                <div className="flex shrink-0 flex-wrap justify-end gap-2">
                  <Button
                    size="sm"
                    variant="secondary"
                    onClick={() => handleTest(printer)}
                    disabled={testingId === printer.id}
                  >
                    {testingId === printer.id ? 'Imprimiendo…' : 'Probar'}
                  </Button>
                  <Button size="sm" variant="danger" onClick={() => handleForget(printer)}>
                    Olvidar
                  </Button>
                </div>
              </li>
            ))}
          </ul>
        )}
      </Card>

      <Card
        title="Áreas de preparación"
        icon={<StoreIcon className="h-4 w-4" />}
        action={
          <button
            type="button"
            onClick={() => areasQuery.refetch()}
            disabled={areasQuery.isFetching}
            className="rounded-lg border border-white/10 p-1.5 text-gray-400 transition hover:bg-white/5 hover:text-white disabled:opacity-50"
            aria-label="Actualizar áreas"
          >
            <RefreshIcon className={`h-3.5 w-3.5 ${areasQuery.isFetching ? 'animate-spin' : ''}`} />
          </button>
        }
      >
        <p className="mb-2 text-xs text-gray-500">
          El sistema define qué producto pertenece a qué área. Aquí eliges en qué impresora se
          imprime cada área en este dispositivo.
        </p>

        {areasQuery.isLoading && (
          <div className="space-y-2">
            {Array.from({ length: 3 }).map((_, index) => (
              <div key={index} className="h-16 animate-pulse rounded-2xl bg-white/5" />
            ))}
          </div>
        )}

        {areasQuery.isError && (
          <p className="text-sm text-gray-400">
            No se pudieron cargar las áreas de preparación.
          </p>
        )}

        {!areasQuery.isLoading && !areasQuery.isError && areas.length === 0 && (
          <p className="mb-2 text-sm text-gray-400">
            La sucursal no tiene áreas de preparación activas.
          </p>
        )}

        {!areasQuery.isLoading && !areasQuery.isError && (
          <ul className="divide-y divide-white/5">
            {areaRows.map((area) => {
              const mapping = mappingByArea.get(area.id)
              const mappedPrinter = mapping ? printerById.get(mapping.printerId) : undefined

              return (
                <li key={area.id} className="space-y-2 py-3">
                  <div className="flex items-center justify-between gap-3">
                    <div className="min-w-0">
                      <p className="truncate text-sm font-medium text-white">{area.name}</p>
                      <p className="text-[11px] text-gray-500">
                        {area.id === 0
                          ? 'Ticket de cuenta'
                          : area.print_ticket
                            ? 'Imprime ticket'
                            : 'No imprime ticket'}
                        {area.system_printer_name
                          ? ` · sistema: ${area.system_printer_name}`
                          : ''}
                      </p>
                    </div>

                    {mappedPrinter && (
                      <Button
                        size="sm"
                        variant="secondary"
                        onClick={() => handleTest(mappedPrinter)}
                        disabled={testingId === mappedPrinter.id}
                      >
                        {testingId === mappedPrinter.id ? 'Imprimiendo…' : 'Probar'}
                      </Button>
                    )}
                  </div>

                  <select
                    value={mapping?.printerId ?? ''}
                    onChange={(event) => handleAssign(area, event.target.value)}
                    className="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 text-sm text-white outline-none transition focus:border-primary"
                  >
                    <option value="">Sin impresora en este dispositivo</option>
                    {printers.map((printer) => (
                      <option key={printer.id} value={printer.id}>
                        {printer.alias}
                      </option>
                    ))}
                  </select>
                </li>
              )
            })}
          </ul>
        )}
      </Card>

      <Card
        title="Actividad reciente"
        icon={<HistoryIcon className="h-4 w-4" />}
        action={
          logs.length > 0 ? (
            <button
              type="button"
              onClick={() => clearPrintLogs()}
              className="text-[11px] font-medium text-gray-500 transition hover:text-white"
            >
              Limpiar
            </button>
          ) : undefined
        }
      >
        {logs.length === 0 ? (
          <p className="text-sm text-gray-400">Sin impresiones registradas en este dispositivo.</p>
        ) : (
          <ul className="divide-y divide-white/5">
            {logs.map((log) => (
              <li key={log.id} className="flex items-center justify-between gap-3 py-2.5">
                <div className="min-w-0">
                  <p className="truncate text-sm text-gray-200">
                    {kindLabels[log.kind]} · {log.areaName}
                  </p>
                  <p className="truncate text-[11px] text-gray-500">
                    {log.printerAlias}
                    {log.orderId ? ` · #${log.orderId}` : ''} · {formatDateTime(log.createdAt)}
                  </p>
                </div>

                <Badge variant={log.status === 'ok' ? 'success' : 'muted'}>
                  {log.status === 'ok' ? 'OK' : 'Error'}
                </Badge>
              </li>
            ))}
          </ul>
        )}
      </Card>
    </div>
  )
}
