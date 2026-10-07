import type { LocalPrinter } from '../db/db'
import { listPrinters, updatePrinter } from '../db/printers'

const COMMON_BLUETOOTH_SERVICES = [
  '0000ff00-0000-1000-8000-00805f9b34fb',
  '0000ffe0-0000-1000-8000-00805f9b34fb',
  '0000ffe5-0000-1000-8000-00805f9b34fb',
  '0000ff90-0000-1000-8000-00805f9b34fb',
  '0000fff0-0000-1000-8000-00805f9b34fb',
  '0000ff01-0000-1000-8000-00805f9b34fb',
  '0000ff10-0000-1000-8000-00805f9b34fb',
  '0000ff30-0000-1000-8000-00805f9b34fb',
  '0000fff5-0000-1000-8000-00805f9b34fb',
  '0000fef5-0000-1000-8000-00805f9b34fb',
  '0000a300-0000-1000-8000-00805f9b34fb',
  '000018f0-0000-1000-8000-00805f9b34fb',
  '49535343-fe7d-4ae5-8fa9-9fafd205e455',
  'e7810a71-73ae-499d-8c15-faa9aef0c3f2',
]

export interface BridgePrintPayload {
  type: 'kitchen' | 'pre_check'
  printer_name?: string
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
  connect(options?: { allowPairing?: boolean }): Promise<void>
  disconnect(): Promise<void>
  writeBytes(data: Uint8Array): Promise<void>
  sendPayload?(payload: BridgePrintPayload): Promise<void>
}

interface ActiveConnection {
  deviceId: string
  server: BluetoothRemoteGATTServer
  characteristic: BluetoothRemoteGATTCharacteristic
  gatt: { service: string; characteristic: string }
}

const grantedDevices = new Map<string, BluetoothDevice>()
const activeConnections = new Map<string, ActiveConnection>()

export function isWebBluetoothSupported(): boolean {
  return typeof navigator !== 'undefined' && 'bluetooth' in navigator
}

export function isGetDevicesSupported(): boolean {
  return isWebBluetoothSupported() && 'getDevices' in navigator.bluetooth
}

function releaseConnections(): void {
  for (const connection of activeConnections.values()) {
    try {
      connection.server.disconnect()
    } catch {
      // Ignorar errores al desconectar.
    }
  }

  activeConnections.clear()
}

const delay = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms))

async function findWritableCharacteristic(server: BluetoothRemoteGATTServer) {
  const services = await server.getPrimaryServices()

  for (const service of services) {
    const characteristics = await service.getCharacteristics()

    for (const characteristic of characteristics) {
      if (characteristic.properties.write || characteristic.properties.writeWithoutResponse) {
        return {
          service: service.uuid,
          characteristic: characteristic.uuid,
          characteristicObject: characteristic,
        }
      }
    }
  }

  throw new Error(
    'La impresora no expone una característica de escritura compatible. Prueba con otra impresora.',
  )
}

function describeBluetoothError(error: unknown): string {
  const message = error instanceof Error ? error.message : String(error)

  if (/globally disabled|not allowed|permission|blocked/i.test(message)) {
    return 'Web Bluetooth está deshabilitado o bloqueado. En Chrome DevTools desactiva la emulación de dispositivo; en escritorio usa Chrome/Edge.'
  }

  if (/cancel|chooser|no device selected/i.test(message)) {
    return 'No se seleccionó ninguna impresora.'
  }

  if (/adapter not available|bluetooth adapter/i.test(message)) {
    return 'Este dispositivo no tiene Bluetooth disponible.'
  }

  return message
}

function describeConnectionError(error: unknown): string {
  const message = error instanceof Error ? error.message : String(error)

  if (/connection attempt failed|connection error|network|failed to connect/i.test(message)) {
    return 'No se pudo conectar con la impresora. Verifica que esté encendida, con batería y cerca. Si persiste: elimina el dispositivo del Bluetooth de Windows, apágalo y enciéndelo, y vuelve a emparejarlo desde el navegador.'
  }

  return message
}

function isDisconnectedError(error: unknown): boolean {
  const message = error instanceof Error ? error.message : String(error)

  return /GATT Server is disconnected|disconnected|not connected|NetworkError|InvalidStateError|connection attempt failed/i.test(
    message,
  )
}

async function openConnection(
  device: BluetoothDevice,
  gatt: { service: string; characteristic: string },
): Promise<ActiveConnection> {
  const existing = activeConnections.get(device.id)

  if (existing?.server.connected) {
    return existing
  }

  activeConnections.delete(device.id)

  if (!device.gatt) {
    throw new Error('La impresora no soporta GATT.')
  }

  const attempts = 3
  let lastError: unknown = null

  for (let attempt = 0; attempt < attempts; attempt += 1) {
    try {
      const server = await device.gatt.connect()
      const service = await server.getPrimaryService(gatt.service)
      const characteristic = await service.getCharacteristic(gatt.characteristic)
      const connection: ActiveConnection = {
        deviceId: device.id,
        server,
        characteristic,
        gatt,
      }
      activeConnections.set(device.id, connection)

      return connection
    } catch (error) {
      lastError = error

      try {
        device.gatt.disconnect()
      } catch {
        // Ignorar errores al desconectar.
      }

      if (attempt < attempts - 1) {
        await delay(600 * (attempt + 1))
      }
    }
  }

  throw new Error(describeConnectionError(lastError))
}

export async function pairBluetoothPrinter(
  extraServices: string[] = [],
  options: { namePrefix?: string } = {},
): Promise<{
  deviceId: string
  deviceName: string
  gatt: { service: string; characteristic: string }
}> {
  if (!isWebBluetoothSupported()) {
    throw new Error('Este dispositivo o navegador no soporta impresión Bluetooth (Web Bluetooth).')
  }

  const optionalServices = [...new Set([...COMMON_BLUETOOTH_SERVICES, ...extraServices])]

  let device: BluetoothDevice

  // Liberar conexiones abiertas antes de abrir el selector: deja la radio lista
  // para emparejar otra impresora y evita bloqueos de la pila Bluetooth.
  releaseConnections()

  const storedName = options.namePrefix?.trim()
  const namePrefix =
    storedName && storedName.length >= 2 && !/^impresora bluetooth$/i.test(storedName)
      ? storedName
      : undefined

  try {
    // Al reconectar una impresora ya guardada se filtra por su nombre para que
    // el selector solo muestre esos dispositivos y no todos los del entorno.
    device = namePrefix
      ? await navigator.bluetooth.requestDevice({
          filters: [{ namePrefix }],
          optionalServices,
        })
      : await navigator.bluetooth.requestDevice({
          acceptAllDevices: true,
          optionalServices,
        })
  } catch (error) {
    throw new Error(describeBluetoothError(error))
  }

  if (!device.gatt) {
    throw new Error('La impresora seleccionada no soporta GATT.')
  }

  let server: BluetoothRemoteGATTServer
  let gatt: Awaited<ReturnType<typeof findWritableCharacteristic>>

  try {
    server = await device.gatt.connect()
    gatt = await findWritableCharacteristic(server)
  } catch (error) {
    throw new Error(describeConnectionError(error))
  }

  grantedDevices.set(device.id, device)
  activeConnections.set(device.id, {
    deviceId: device.id,
    server,
    characteristic: gatt.characteristicObject,
    gatt: { service: gatt.service, characteristic: gatt.characteristic },
  })

  return {
    deviceId: device.id,
    deviceName: device.name ?? 'Impresora Bluetooth',
    gatt: { service: gatt.service, characteristic: gatt.characteristic },
  }
}

export class BluetoothTransport implements PrinterTransport {
  private device: BluetoothDevice | null = null
  private connection: ActiveConnection | null = null
  private readonly printer: LocalPrinter

  constructor(printer: LocalPrinter) {
    this.printer = printer
  }

  private async resolveDevice(): Promise<BluetoothDevice> {
    if (!isWebBluetoothSupported()) {
      throw new Error('Web Bluetooth no está disponible en este dispositivo.')
    }

    const deviceId = this.printer.bluetoothDeviceId

    if (deviceId) {
      const cached = grantedDevices.get(deviceId)

      if (cached) {
        return cached
      }
    }

    const devices = await this.getGrantedDevices()

    if (deviceId) {
      const byId = devices.find((device) => device.id === deviceId)

      if (byId) {
        grantedDevices.set(byId.id, byId)
        return byId
      }
    }

    // El id puede cambiar entre sesiones del navegador: buscar por nombre.
    const storedName = this.printer.bluetoothDeviceName?.trim().toLowerCase()

    if (storedName) {
      const byName = devices.find(
        (device) => device.name?.trim().toLowerCase() === storedName,
      )

      if (byName) {
        await this.adoptDevice(byName)
        return byName
      }
    }

    // Si Chrome solo tiene autorizado un dispositivo, es la impresora guardada
    // aunque el id o el nombre hayan cambiado (p. ej. "dispositivo desconocido").
    if (devices.length === 1) {
      await this.adoptDevice(devices[0])
      return devices[0]
    }

    // Con varios dispositivos autorizados (u otros equipos), comprobar cuál
    // expone el servicio/característica guardados.
    const probed = await this.findDeviceByGatt(devices)

    if (probed) {
      return probed
    }

    throw new Error(
      `La impresora "${this.printer.alias}" no está disponible. Vuelve a emparejarla.`,
    )
  }

  private async findDeviceByGatt(devices: BluetoothDevice[]): Promise<BluetoothDevice | null> {
    const gatt = this.printer.gatt

    if (!gatt) {
      return null
    }

    for (const device of devices.slice(0, 5)) {
      if (!device.gatt) {
        continue
      }

      let connected = false

      try {
        const server = await Promise.race([
          device.gatt.connect(),
          new Promise<never>((_, reject) =>
            setTimeout(() => reject(new Error('Bluetooth timeout')), 4000),
          ),
        ])

        connected = true

        const service = await server.getPrimaryService(gatt.service)
        await service.getCharacteristic(gatt.characteristic)

        await this.adoptDevice(device)
        return device
      } catch {
        if (connected) {
          try {
            device.gatt.disconnect()
          } catch {
            // Ignorar errores al desconectar.
          }
        }
      }
    }

    return null
  }

  private async getGrantedDevices(): Promise<BluetoothDevice[]> {
    if (!('getDevices' in navigator.bluetooth)) {
      return []
    }

    try {
      const devices = await navigator.bluetooth.getDevices()

      console.debug(
        '[bluetooth] dispositivos autorizados:',
        devices.map((device) => ({ id: device.id, name: device.name })),
      )

      return devices
    } catch (error) {
      console.warn('[bluetooth] getDevices falló:', error)
      return []
    }
  }

  private async adoptDevice(device: BluetoothDevice): Promise<void> {
    grantedDevices.set(device.id, device)
    this.printer.bluetoothDeviceId = device.id

    const deviceName = device.name?.trim()

    if (deviceName) {
      this.printer.bluetoothDeviceName = deviceName
    }

    try {
      await updatePrinter(this.printer.id, {
        bluetoothDeviceId: device.id,
        ...(deviceName ? { bluetoothDeviceName: deviceName } : {}),
      })
    } catch {
      // Si no se puede persistir, la sesión actual sigue funcionando.
    }
  }

  async connect(options: { allowPairing?: boolean } = {}): Promise<void> {
    if (this.connection?.server.connected) {
      return
    }

    let gatt = this.printer.gatt

    if (!gatt) {
      throw new Error('La impresora no tiene configuración GATT guardada.')
    }

    let device: BluetoothDevice | null = null

    try {
      device = await this.resolveDevice()
    } catch (error) {
      // Si el usuario lo pidió (botón Probar), abrir el selector para renovar
      // el permiso sin obligarlo a pasar por Ajustes.
      if (!options.allowPairing) {
        throw error
      }

      // Tras recargar la página el permiso/adaptador puede tardar unos
      // segundos en estar listo: reintentar recuperar antes del selector.
      for (const wait of [500, 800, 1000, 1200]) {
        await delay(wait)

        try {
          device = await this.resolveDevice()
          break
        } catch {
          // Se reintenta hasta agotar las esperas.
        }
      }

      if (!device) {
        try {
          const paired = await pairBluetoothPrinter(this.printer.bluetoothServices ?? [], {
            namePrefix: this.printer.bluetoothDeviceName?.trim() || undefined,
          })

          this.printer.bluetoothDeviceId = paired.deviceId
          this.printer.bluetoothDeviceName = paired.deviceName
          this.printer.gatt = paired.gatt
          gatt = paired.gatt

          try {
            await updatePrinter(this.printer.id, {
              bluetoothDeviceId: paired.deviceId,
              bluetoothDeviceName: paired.deviceName,
              gatt: paired.gatt,
            })
          } catch {
            // Si no se puede persistir, la sesión actual sigue funcionando.
          }

          device = await this.resolveDevice()
        } catch (pairingError) {
          // Al abrir el selector, Chrome descubre/refresca los dispositivos
          // autorizados: si el usuario canceló pero la impresora ya estaba
          // autorizada, recuperarla y continuar sin obligarlo a reintentar.
          try {
            device = await this.resolveDevice()
          } catch {
            throw pairingError
          }
        }
      }
    }

    this.device = device
    this.connection = await openConnection(device, gatt)
  }

  private async writeChunk(chunk: Uint8Array): Promise<void> {
    const connection = this.connection

    if (!connection || !connection.server.connected) {
      throw new Error('GATT Server is disconnected.')
    }

    const value = new Uint8Array(chunk)

    if (connection.characteristic.properties.writeWithoutResponse) {
      await connection.characteristic.writeValueWithoutResponse(value)
    } else {
      await connection.characteristic.writeValue(value)
    }
  }

  async writeBytes(data: Uint8Array): Promise<void> {
    await this.connect()

    const chunkSize = 180

    for (let offset = 0; offset < data.length; offset += chunkSize) {
      const chunk = data.slice(offset, offset + chunkSize)

      try {
        await this.writeChunk(chunk)
      } catch (error) {
        if (!isDisconnectedError(error) || !this.device || !this.printer.gatt) {
          throw error
        }

        try {
          this.connection = await openConnection(this.device, this.printer.gatt)
          await this.writeChunk(chunk)
        } catch {
          throw new Error(
            'La impresora se desconectó. Verifica que esté encendida y cerca, y vuelve a intentar.',
          )
        }
      }

      await delay(20)
    }
  }

  disconnect(): Promise<void> {
    // La conexión se mantiene viva para reutilizarla en la próxima impresión.
    this.connection = this.connection?.server.connected ? this.connection : null

    return Promise.resolve()
  }
}

export async function restoreSavedConnections(): Promise<void> {
  if (!isWebBluetoothSupported()) {
    return
  }

  let printers: LocalPrinter[]

  try {
    printers = await listPrinters()
  } catch {
    return
  }

  for (const printer of printers) {
    if (printer.transport !== 'bluetooth' || !printer.gatt) {
      continue
    }

    const deviceId = printer.bluetoothDeviceId

    if (deviceId && activeConnections.get(deviceId)?.server.connected) {
      continue
    }

    try {
      await new BluetoothTransport(printer).connect()
    } catch {
      // Sin conexión: se reintentará en el siguiente ciclo.
    }
  }
}

export function startConnectionKeepAlive(intervalMs = 60_000): () => void {
  let stopped = false

  const reconnectStale = async () => {
    if (stopped || !navigator.onLine || document.visibilityState !== 'visible') {
      return
    }

    for (const connection of [...activeConnections.values()]) {
      if (connection.server.connected) {
        continue
      }

      const device = grantedDevices.get(connection.deviceId)

      if (!device) {
        continue
      }

      try {
        await openConnection(device, connection.gatt)
      } catch {
        // Se conserva la conexión para reintentar en el siguiente ciclo.
        activeConnections.set(connection.deviceId, connection)
      }
    }
  }

  const keepSynced = async () => {
    if (stopped || !navigator.onLine || document.visibilityState !== 'visible') {
      return
    }

    await reconnectStale()
    await restoreSavedConnections()
  }

  const interval = window.setInterval(() => {
    void keepSynced()
  }, intervalMs)

  const onVisibility = () => {
    if (document.visibilityState === 'visible') {
      void keepSynced()
    }
  }

  const onOnline = () => {
    void keepSynced()
  }

  document.addEventListener('visibilitychange', onVisibility)
  window.addEventListener('online', onOnline)
  void keepSynced()

  // Tras recargar, Chrome puede tardar unos segundos en exponer los
  // dispositivos autorizados: insistir al inicio para reconectar solo.
  const warmUps = [1000, 2500, 4500, 7000, 10000].map((wait) =>
    window.setTimeout(() => {
      void keepSynced()
    }, wait),
  )

  return () => {
    stopped = true
    warmUps.forEach((timer) => window.clearTimeout(timer))
    window.clearInterval(interval)
    document.removeEventListener('visibilitychange', onVisibility)
    window.removeEventListener('online', onOnline)
  }
}

export function normalizeBridgeUrl(url: string): string {
  return (url.trim() || 'http://localhost:8000').replace(/\/+$/, '')
}

export async function listBridgePrinters(bridgeUrl: string): Promise<string[]> {
  const base = normalizeBridgeUrl(bridgeUrl)
  const controller = new AbortController()
  const timer = setTimeout(() => controller.abort(), 3000)

  try {
    const response = await fetch(`${base}/api/printer/list`, { signal: controller.signal })

    if (!response.ok) {
      throw new Error(`El agente respondió ${response.status}`)
    }

    const data: unknown = await response.json()
    const printers =
      typeof data === 'object' && data !== null && 'printers' in data
        ? (data as { printers: unknown }).printers
        : []

    return Array.isArray(printers) ? printers.map(String) : []
  } catch {
    throw new Error(`No se pudo conectar con el agente local en ${base}.`)
  } finally {
    clearTimeout(timer)
  }
}

export class BridgeTransport implements PrinterTransport {
  private readonly printer: LocalPrinter

  constructor(printer: LocalPrinter) {
    this.printer = printer
  }

  private baseUrl(): string {
    return normalizeBridgeUrl(this.printer.bridgeUrl ?? 'http://localhost:8000')
  }

  async connect(): Promise<void> {
    if (!this.printer.bridgePrinterName) {
      throw new Error('No se seleccionó una impresora del agente local.')
    }

    await listBridgePrinters(this.baseUrl())
  }

  writeBytes(): Promise<void> {
    return Promise.reject(new Error('El bridge local no acepta bytes crudos.'))
  }

  async sendPayload(payload: BridgePrintPayload): Promise<void> {
    let response: Response

    try {
      response = await fetch(`${this.baseUrl()}/api/printer/raw`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          ...payload,
          printer_name: this.printer.bridgePrinterName,
        }),
      })
    } catch {
      throw new Error(`No se pudo conectar con el agente local en ${this.baseUrl()}.`)
    }

    if (!response.ok) {
      throw new Error(`El agente local respondió ${response.status}.`)
    }
  }

  disconnect(): Promise<void> {
    return Promise.resolve()
  }
}

export function createTransport(printer: LocalPrinter): PrinterTransport {
  return printer.transport === 'bluetooth'
    ? new BluetoothTransport(printer)
    : new BridgeTransport(printer)
}
