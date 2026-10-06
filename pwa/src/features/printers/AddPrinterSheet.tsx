import { useState } from 'react'
import { Button } from '../../components/ui/Button'
import { Sheet } from '../../components/ui/Sheet'
import type { LocalPrinter } from '../../lib/db/db'
import { savePrinter } from '../../lib/db/printers'
import { parseServiceList } from '../../lib/printing/service-list'
import {
  isWebBluetoothSupported,
  listBridgePrinters,
  pairBluetoothPrinter,
} from '../../lib/printing/transport'
import { useToastStore } from '../../stores/toastStore'

interface BluetoothPair {
  deviceId: string
  deviceName: string
  gatt: { service: string; characteristic: string }
}

interface AddPrinterSheetProps {
  printers: LocalPrinter[]
  onClose: () => void
  onSaved: () => void
}

export function AddPrinterSheet({ printers, onClose, onSaved }: AddPrinterSheetProps) {
  const pushToast = useToastStore((state) => state.push)

  const [kind, setKind] = useState<'bluetooth' | 'bridge'>('bluetooth')
  const [alias, setAlias] = useState('')
  const [width, setWidth] = useState<58 | 80>(58)
  const [copies, setCopies] = useState(1)
  const [paired, setPaired] = useState<BluetoothPair | null>(null)
  const [pairing, setPairing] = useState(false)
  const [customServices, setCustomServices] = useState('')
  const [bridgeUrl, setBridgeUrl] = useState('http://localhost:8000')
  const [bridgePrinters, setBridgePrinters] = useState<string[]>([])
  const [selectedBridge, setSelectedBridge] = useState('')
  const [loadingBridge, setLoadingBridge] = useState(false)
  const [saving, setSaving] = useState(false)

  const bluetoothSupported = isWebBluetoothSupported()

  async function handlePair() {
    setPairing(true)

    try {
      const result = await pairBluetoothPrinter(parseServiceList(customServices))
      setPaired(result)
      setAlias((current) => current || result.deviceName)
      pushToast('Impresora Bluetooth lista. Guárdala para asignarla a un área.', 'success')

      const duplicate = printers.find(
        (printer) =>
          printer.transport === 'bluetooth' &&
          printer.bluetoothDeviceName?.trim().toLowerCase() ===
            result.deviceName.trim().toLowerCase(),
      )

      if (duplicate) {
        pushToast(
          `Esa impresora ya está guardada como "${duplicate.alias}". Si guardas, tendrás dos entradas para el mismo equipo.`,
          'info',
        )
      }
    } catch (error) {
      pushToast(error instanceof Error ? error.message : 'No se pudo emparejar la impresora', 'error')
    } finally {
      setPairing(false)
    }
  }

  async function handleDetectBridge() {
    setLoadingBridge(true)

    try {
      const printers = await listBridgePrinters(bridgeUrl)
      setBridgePrinters(printers)

      if (printers.length === 0) {
        pushToast('El agente local no reportó impresoras instaladas.', 'info')
      }
    } catch (error) {
      pushToast(error instanceof Error ? error.message : 'No se pudo consultar el agente', 'error')
    } finally {
      setLoadingBridge(false)
    }
  }

  async function handleSave() {
    if (!alias.trim()) {
      pushToast('Escribe un nombre para la impresora.', 'error')
      return
    }

    if (kind === 'bluetooth' && !paired) {
      pushToast('Empareja una impresora Bluetooth primero.', 'error')
      return
    }

    if (kind === 'bridge' && !selectedBridge) {
      pushToast('Selecciona una impresora del agente local.', 'error')
      return
    }

    setSaving(true)

    try {
      const services = parseServiceList(customServices)

      await savePrinter({
        alias: alias.trim(),
        transport: kind,
        width,
        copies: Math.max(1, Math.min(5, copies)),
        ...(kind === 'bluetooth' && paired
          ? {
              bluetoothDeviceId: paired.deviceId,
              bluetoothDeviceName: paired.deviceName,
              bluetoothServices: services.length > 0 ? services : undefined,
              gatt: paired.gatt,
            }
          : {}),
        ...(kind === 'bridge'
          ? { bridgeUrl: bridgeUrl.trim(), bridgePrinterName: selectedBridge }
          : {}),
      })

      pushToast('Impresora guardada en este dispositivo.', 'success')
      onSaved()
      onClose()
    } catch {
      pushToast('No se pudo guardar la impresora.', 'error')
    } finally {
      setSaving(false)
    }
  }

  const inputClass =
    'w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-gray-600 focus:border-primary'

  return (
    <Sheet open title="Agregar impresora" onClose={onClose}>
      <div className="mb-4 grid grid-cols-2 gap-1 rounded-2xl border border-white/5 bg-white/5 p-1">
        <button
          type="button"
          onClick={() => setKind('bluetooth')}
          className={`rounded-xl py-2 text-xs font-semibold transition ${
            kind === 'bluetooth' ? 'bg-primary text-black' : 'text-gray-400 hover:text-white'
          }`}
        >
          Bluetooth
        </button>
        <button
          type="button"
          onClick={() => setKind('bridge')}
          className={`rounded-xl py-2 text-xs font-semibold transition ${
            kind === 'bridge' ? 'bg-primary text-black' : 'text-gray-400 hover:text-white'
          }`}
        >
          Bridge local
        </button>
      </div>

      {kind === 'bluetooth' ? (
        <div className="space-y-4">
          {!bluetoothSupported && (
            <div className="rounded-2xl border border-primary/20 bg-primary/10 px-4 py-3 text-xs text-primary">
              Este navegador no soporta Web Bluetooth. En iOS/Safari usa un bridge local en una PC
              Windows.
            </div>
          )}

          <Button onClick={handlePair} disabled={pairing || !bluetoothSupported} className="w-full">
            {pairing ? 'Buscando…' : paired ? 'Emparejar otra impresora' : 'Emparejar impresora Bluetooth'}
          </Button>

          {paired && (
            <div className="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-300">
              Bluetooth listo: {paired.deviceName}
            </div>
          )}

          <div>
            <label
              htmlFor="custom-services"
              className="mb-2 block text-[11px] font-semibold uppercase tracking-wider text-gray-500"
            >
              Servicios GATT (opcional)
            </label>
            <input
              id="custom-services"
              value={customServices}
              onChange={(event) => setCustomServices(event.target.value)}
              placeholder="ffe0, ff00, 0000fff0-0000-1000-8000-00805f9b34fb"
              className={inputClass}
            />
            <p className="mt-1 text-[10px] text-gray-500">
              Si tu impresora aparece como “dispositivo desconocido o no compatible”, escribe aquí
              su servicio (4 o 16 caracteres hex, separados por coma) antes de emparejar.
            </p>
          </div>
        </div>
      ) : (
        <div className="space-y-4">
          <div>
            <label htmlFor="bridge-url" className="mb-2 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">
              URL del agente local
            </label>
            <input
              id="bridge-url"
              value={bridgeUrl}
              onChange={(event) => setBridgeUrl(event.target.value)}
              placeholder="http://localhost:8000"
              className={inputClass}
            />
          </div>

          <Button
            variant="secondary"
            onClick={handleDetectBridge}
            disabled={loadingBridge}
            className="w-full"
          >
            {loadingBridge ? 'Consultando…' : 'Detectar impresoras instaladas'}
          </Button>

          {bridgePrinters.length > 0 && (
            <div>
              <label htmlFor="bridge-printer" className="mb-2 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                Impresora de Windows
              </label>
              <select
                id="bridge-printer"
                value={selectedBridge}
                onChange={(event) => {
                  setSelectedBridge(event.target.value)
                  setAlias((current) => current || event.target.value)
                }}
                className={inputClass}
              >
                <option value="">Selecciona una impresora</option>
                {bridgePrinters.map((name) => (
                  <option key={name} value={name}>
                    {name}
                  </option>
                ))}
              </select>
            </div>
          )}
        </div>
      )}

      <div className="mt-4 space-y-4">
        <div>
          <label htmlFor="printer-alias" className="mb-2 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">
            Nombre en este dispositivo
          </label>
          <input
            id="printer-alias"
            value={alias}
            onChange={(event) => setAlias(event.target.value)}
            placeholder="Ej. Cocina BT"
            className={inputClass}
          />
        </div>

        <div className="grid grid-cols-2 gap-3">
          <div>
            <label htmlFor="printer-width" className="mb-2 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">
              Ancho de papel
            </label>
            <select
              id="printer-width"
              value={width}
              onChange={(event) => setWidth(Number(event.target.value) as 58 | 80)}
              className={inputClass}
            >
              <option value={58}>58 mm</option>
              <option value={80}>80 mm</option>
            </select>
          </div>

          <div>
            <label htmlFor="printer-copies" className="mb-2 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">
              Copias
            </label>
            <select
              id="printer-copies"
              value={copies}
              onChange={(event) => setCopies(Number(event.target.value))}
              className={inputClass}
            >
              {[1, 2, 3].map((value) => (
                <option key={value} value={value}>
                  {value}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      <div className="mt-5 flex justify-end gap-2">
        <Button variant="secondary" onClick={onClose} disabled={saving}>
          Cancelar
        </Button>
        <Button onClick={handleSave} disabled={saving}>
          {saving ? 'Guardando…' : 'Guardar impresora'}
        </Button>
      </div>
    </Sheet>
  )
}
