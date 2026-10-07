package com.softmor.gestionalfoodprinter

import android.annotation.SuppressLint
import android.bluetooth.BluetoothAdapter
import android.bluetooth.BluetoothGatt
import android.bluetooth.BluetoothGattCallback
import android.bluetooth.BluetoothGattCharacteristic
import android.bluetooth.BluetoothProfile
import android.bluetooth.BluetoothSocket
import android.content.Context
import java.io.IOException
import java.util.UUID
import java.util.concurrent.CountDownLatch
import java.util.concurrent.TimeUnit

object BluetoothPrinter {
    private val SPP_UUID: UUID = UUID.fromString("00001101-0000-1000-8000-00805F9B34FB")
    private val lock = Any()

    private var appContext: Context? = null
    private var printers: List<SavedPrinter> = emptyList()

    private val sockets = mutableMapOf<String, BluetoothSocket>()
    private val gatts = mutableMapOf<String, BluetoothGatt>()
    private val gattCharacteristics = mutableMapOf<String, BluetoothGattCharacteristic>()
    private val connectedGatts = mutableSetOf<String>()
    private val errors = mutableMapOf<String, String>()

    private val knownBleServices = setOf(
        "0000ff00-0000-1000-8000-00805f9b34fb",
        "0000ffe0-0000-1000-8000-00805f9b34fb",
        "0000ffe5-0000-1000-8000-00805f9b34fb",
        "0000ff90-0000-1000-8000-00805f9b34fb",
        "0000fff0-0000-1000-8000-00805f9b34fb",
        "0000ff01-0000-1000-8000-00805f9b34fb",
        "0000ff10-0000-1000-8000-00805f9b34fb",
        "0000ff30-0000-1000-8000-00805f9b34fb",
        "0000fff5-0000-1000-8000-00805f9b34fb",
        "0000fef5-0000-1000-8000-00805f9b34fb",
        "0000a300-0000-1000-8000-00805f9b34fb",
        "000018f0-0000-1000-8000-00805f9b34fb",
        "49535343-fe7d-4ae5-8fa9-9fafd205e455",
        "e7810a71-73ae-499d-8c15-faa9aef0c3f2",
    )

    fun restore(context: Context) {
        synchronized(lock) {
            appContext = context.applicationContext
            printers = AgentConfig.printers(context)
        }
    }

    fun configuredPrinters(): List<SavedPrinter> = synchronized(lock) { printers.toList() }

    fun lastError(address: String): String? = synchronized(lock) { errors[address] }

    fun setPrinters(context: Context, next: List<SavedPrinter>) {
        synchronized(lock) {
            appContext = context.applicationContext

            val keep = next.map { it.address }.toSet()

            sockets.keys.filter { it !in keep }.forEach { address -> closeSocketLocked(address) }
            gatts.keys.filter { it !in keep }.forEach { address -> closeGattLocked(address) }
            errors.keys.filter { it !in keep }.forEach { address -> errors.remove(address) }

            printers = next
            AgentConfig.savePrinters(context, next)
        }
    }

    fun isConnected(address: String): Boolean = synchronized(lock) {
        sockets[address]?.isConnected == true || connectedGatts.contains(address)
    }

    private fun briefMessage(error: Throwable): String {
        val raw = error.message ?: "error"

        return when {
            raw.contains("BLUETOOTH_SCAN", ignoreCase = true) ||
                raw.contains("SecurityException", ignoreCase = true) -> "sin permiso de Bluetooth"

            raw.contains("read failed", ignoreCase = true) ||
                raw.contains("socket might closed", ignoreCase = true) -> "conexión rechazada"

            raw.contains("Service discovery", ignoreCase = true) -> "servicio no disponible"

            else -> raw.lineSequence().firstOrNull()?.trim()?.take(80) ?: "error"
        }
    }

    fun ensureConnected(address: String): Boolean {
        synchronized(lock) {
            val target = printers.firstOrNull { it.address == address } ?: return false

            if (isConnected(address)) {
                errors.remove(address)
                return true
            }

            return try {
                ensureSppLocked(target)
                errors.remove(address)
                true
            } catch (sppError: Exception) {
                try {
                    ensureBleLocked(target)
                    errors.remove(address)
                    true
                } catch (bleError: Exception) {
                    errors[address] =
                        "SPP: ${briefMessage(sppError)} · BLE: ${briefMessage(bleError)}"
                    false
                }
            }
        }
    }

    @SuppressLint("MissingPermission")
    fun send(printerName: String, data: ByteArray) {
        val target = synchronized(lock) {
            printers.firstOrNull {
                it.name.equals(printerName, ignoreCase = true) ||
                    it.address.equals(printerName, ignoreCase = true)
            } ?: printers.singleOrNull()
            ?: throw IOException("No hay impresora configurada para \"$printerName\"")
        }

        synchronized(lock) {
            try {
                val socket = ensureSppLocked(target)
                socket.outputStream.write(data)
                socket.outputStream.flush()
                errors.remove(target.address)
                return
            } catch (sppError: Exception) {
                closeSocketLocked(target.address)

                try {
                    writeBleLocked(target, data)
                    errors.remove(target.address)
                    return
                } catch (bleError: Exception) {
                    val message =
                        "SPP: ${briefMessage(sppError)} · BLE: ${briefMessage(bleError)}"
                    errors[target.address] = message
                    throw IOException("No se pudo imprimir en \"${target.name}\": $message")
                }
            }
        }
    }

    fun disconnect() {
        synchronized(lock) {
            sockets.keys.toList().forEach { address -> closeSocketLocked(address) }
            gatts.keys.toList().forEach { address -> closeGattLocked(address) }
        }
    }

    @SuppressLint("MissingPermission")
    private fun ensureSppLocked(target: SavedPrinter): BluetoothSocket {
        val existing = sockets[target.address]

        if (existing?.isConnected == true) {
            return existing
        }

        closeSocketLocked(target.address)

        val adapter = requireAdapter()

        try {
            adapter.cancelDiscovery()
        } catch (_: SecurityException) {
            // Sin permiso de escaneo: no es indispensable para conectar.
        }

        val device = adapter.getRemoteDevice(target.address)
        val created = device.createRfcommSocketToServiceRecord(SPP_UUID)
        created.connect()
        sockets[target.address] = created

        return created
    }

    private fun ensureBleLocked(target: SavedPrinter): BluetoothGattCharacteristic {
        val existing = gattCharacteristics[target.address]

        if (existing != null && connectedGatts.contains(target.address)) {
            return existing
        }

        val context = appContext ?: throw IOException("Contexto no disponible")
        val adapter = requireAdapter()

        closeGattLocked(target.address)

        val device = adapter.getRemoteDevice(target.address)
        val connectedLatch = CountDownLatch(1)
        val servicesLatch = CountDownLatch(1)
        var discoveryError: String? = null

        val callback = object : BluetoothGattCallback() {
            override fun onConnectionStateChange(gatt: BluetoothGatt, status: Int, newState: Int) {
                if (newState == BluetoothProfile.STATE_CONNECTED) {
                    connectedGatts.add(target.address)
                    connectedLatch.countDown()
                    gatt.discoverServices()
                } else {
                    connectedGatts.remove(target.address)

                    if (newState == BluetoothProfile.STATE_DISCONNECTED && status != 0) {
                        discoveryError = "conexión rechazada (status $status)"
                        connectedLatch.countDown()
                        servicesLatch.countDown()
                    }
                }
            }

            override fun onServicesDiscovered(gatt: BluetoothGatt, status: Int) {
                if (status == BluetoothGatt.GATT_SUCCESS) {
                    val found = findWritableCharacteristic(gatt)

                    if (found != null) {
                        gattCharacteristics[target.address] = found
                    } else {
                        discoveryError = "sin característica de escritura BLE"
                    }
                } else {
                    discoveryError = "descubrimiento de servicios falló ($status)"
                }

                servicesLatch.countDown()
            }
        }

        val gatt = device.connectGatt(context, false, callback)
            ?: throw IOException("No se pudo abrir GATT")

        gatts[target.address] = gatt

        if (!connectedLatch.await(8, TimeUnit.SECONDS)) {
            closeGattLocked(target.address)
            throw IOException("tiempo de conexión BLE agotado")
        }

        if (!connectedGatts.contains(target.address)) {
            closeGattLocked(target.address)
            throw IOException(discoveryError ?: "no se pudo conectar por BLE")
        }

        if (!servicesLatch.await(8, TimeUnit.SECONDS)) {
            closeGattLocked(target.address)
            throw IOException("tiempo de descubrimiento BLE agotado")
        }

        return gattCharacteristics[target.address]
            ?: run {
                closeGattLocked(target.address)
                throw IOException(discoveryError ?: "sin característica de escritura BLE")
            }
    }

    private fun writeBleLocked(target: SavedPrinter, data: ByteArray) {
        val gatt = gatts[target.address] ?: throw IOException("GATT no disponible")
        val characteristic = ensureBleLocked(target)
        val supportsNoResponse =
            (characteristic.properties and BluetoothGattCharacteristic.PROPERTY_WRITE_NO_RESPONSE) != 0

        var offset = 0
        val chunkSize = 20

        while (offset < data.size) {
            val chunk = data.copyOfRange(offset, minOf(offset + chunkSize, data.size))

            characteristic.writeType = if (supportsNoResponse) {
                BluetoothGattCharacteristic.WRITE_TYPE_NO_RESPONSE
            } else {
                BluetoothGattCharacteristic.WRITE_TYPE_DEFAULT
            }
            characteristic.value = chunk

            if (!gatt.writeCharacteristic(characteristic)) {
                closeGattLocked(target.address)
                throw IOException("no se pudo escribir por BLE")
            }

            offset += chunk.size
            Thread.sleep(20)
        }
    }

    private fun findWritableCharacteristic(gatt: BluetoothGatt): BluetoothGattCharacteristic? {
        val candidates = mutableListOf<BluetoothGattCharacteristic>()

        gatt.services?.forEach { service ->
            service.characteristics?.forEach { characteristic ->
                val writable =
                    (characteristic.properties and BluetoothGattCharacteristic.PROPERTY_WRITE) != 0 ||
                        (characteristic.properties and BluetoothGattCharacteristic.PROPERTY_WRITE_NO_RESPONSE) != 0

                if (writable) {
                    candidates.add(characteristic)

                    if (service.uuid.toString().lowercase() in knownBleServices) {
                        return characteristic
                    }
                }
            }
        }

        return candidates.firstOrNull()
    }

    @SuppressLint("MissingPermission")
    private fun requireAdapter(): BluetoothAdapter {
        val adapter = BluetoothAdapter.getDefaultAdapter()
            ?: throw IOException("Este dispositivo no tiene Bluetooth")

        if (!adapter.isEnabled) {
            throw IOException("El Bluetooth está apagado")
        }

        return adapter
    }

    private fun closeSocketLocked(address: String) {
        val socket = sockets.remove(address) ?: return

        try {
            socket.outputStream?.close()
        } catch (_: Exception) {
        }

        try {
            socket.close()
        } catch (_: Exception) {
        }
    }

    private fun closeGattLocked(address: String) {
        gattCharacteristics.remove(address)
        connectedGatts.remove(address)

        val gatt = gatts.remove(address) ?: return

        try {
            gatt.disconnect()
        } catch (_: Exception) {
        }

        try {
            gatt.close()
        } catch (_: Exception) {
        }
    }
}
