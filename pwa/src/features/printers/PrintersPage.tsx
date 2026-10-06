import { useQuery } from '@tanstack/react-query'
import { useLiveQuery } from 'dexie-react-hooks'
import { useEffect, useState } from 'react'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { ConfirmDialog } from '../../components/ui/ConfirmDialog'
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
import { printTestTicket, rePairPrinter } from '../../lib/printing/printService'
import { isWebBluetoothSupported } from '../../lib/printing/transport'
import { useAuthStore } from '../../stores/authStore'
import { useToastStore } from '../../stores/toastStore'
import { AddPrinterSheet } from './AddPrinterSheet'

function transportLabel(printer: LocalPrinter): string {
  return printer.transport === 'bluetooth' ? 'Bluetooth' : 'Bridge local'
}

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

  const [addOpen, setAddOpen] = useState(false)
  const [testingId, setTestingId] = useState<string | null>(null)
  const [reconnectTarget, setReconnectTarget] = useState<LocalPrinter | null>(null)
  const [repairing, setRepairing] = useState(false)

  const printers = useLiveQuery(() => listPrinters(), []) ?? []
  const mappings =
    useLiveQuery(
      () => (tenantId && branchId ? getMappings(tenantId, branchId) : Promise.resolve([])),
      [tenantId, branchId],
    ) ?? []
  const settings = useLiveQuery(() => readSettings(), [])
  const logs = useLiveQuery(() => listRecentPrintLogs(8), []) ?? []

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
      await printTestTicket(printer, { allowPairing: true })
      pushToast(`Prueba enviada a ${printer.alias}.`, 'success')
    } catch (error) {
      const message = error instanceof Error ? error.message : 'No se pudo imprimir la prueba'
      pushToast(message, 'error')

      if (
        printer.transport === 'bluetooth' &&
        /no está disponible|emparej|not found|conectar|conexión|connection/i.test(message)
      ) {
        setReconnectTarget(printer)
      }
    } finally {
      setTestingId(null)
    }
  }

  async function handleRePair() {
    if (!reconnectTarget) {
      return
    }

    setRepairing(true)

    try {
      const previousName = reconnectTarget.bluetoothDeviceName
      const updated = await rePairPrinter(reconnectTarget)
      setReconnectTarget(null)

      if (
        previousName &&
        updated.bluetoothDeviceName &&
        previousName.trim().toLowerCase() !== updated.bluetoothDeviceName.trim().toLowerCase()
      ) {
        pushToast(
          `Se emparejó "${updated.bluetoothDeviceName}" en lugar de "${previousName}".`,
          'info',
        )
      } else {
        pushToast('Impresora re-emparejada.', 'success')
      }

      await handleTest(updated)
    } catch (error) {
      pushToast(error instanceof Error ? error.message : 'No se pudo emparejar la impresora', 'error')
    } finally {
      setRepairing(false)
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
        <div className="flex items-start justify-between gap-3">
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

          <Button size="sm" onClick={() => setAddOpen(true)}>
            Agregar
          </Button>
        </div>
      </Card>

      {!isWebBluetoothSupported() && printers.every((printer) => printer.transport !== 'bluetooth') && (
        <div className="rounded-2xl border border-primary/20 bg-primary/10 px-4 py-3 text-xs text-primary">
          Este navegador no soporta impresión Bluetooth. Puedes usar el bridge local en una PC
          Windows con el agente instalado.
        </div>
      )}

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
            Aún no has agregado impresoras en este dispositivo. La configuración no se comparte con
            otros dispositivos.
          </p>
        ) : (
          <ul className="divide-y divide-white/5">
            {printers.map((printer) => (
              <li key={printer.id} className="flex items-center justify-between gap-3 py-3">
                <div className="min-w-0">
                  <p className="truncate text-sm font-medium text-white">{printer.alias}</p>
                  <p className="truncate text-[11px] text-gray-500">
                    {transportLabel(printer)} · {printer.width}mm · {printer.copies} copia(s)
                    {printer.bridgePrinterName ? ` · ${printer.bridgePrinterName}` : ''}
                  </p>
                </div>

                <div className="flex shrink-0 gap-2">
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
                        {printer.alias} ({transportLabel(printer)})
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

      {addOpen && (
        <AddPrinterSheet
          printers={printers}
          onClose={() => setAddOpen(false)}
          onSaved={() => undefined}
        />
      )}

      <ConfirmDialog
        open={reconnectTarget !== null}
        title="Volver a emparejar"
        message={`La impresora "${reconnectTarget?.alias ?? ''}" no está disponible en este dispositivo. Selecciónala de nuevo en el cuadro de Bluetooth para renovar el permiso.`}
        confirmLabel="Emparejar"
        loading={repairing}
        onCancel={() => setReconnectTarget(null)}
        onConfirm={handleRePair}
      />
    </div>
  )
}
