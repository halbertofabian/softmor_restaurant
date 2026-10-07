package com.softmor.gestionalfoodprinter

import android.annotation.SuppressLint
import android.bluetooth.BluetoothAdapter
import android.bluetooth.BluetoothSocket
import android.content.Context
import java.io.IOException
import java.util.UUID

object BluetoothPrinter {
    private val SPP_UUID: UUID = UUID.fromString("00001101-0000-1000-8000-00805F9B34FB")
    private val lock = Any()
    private val sockets = mutableMapOf<String, BluetoothSocket>()
    private var printers: List<SavedPrinter> = emptyList()

    fun restore(context: Context) {
        synchronized(lock) {
            printers = AgentConfig.printers(context)
        }
    }

    fun configuredPrinters(): List<SavedPrinter> = synchronized(lock) { printers.toList() }

    fun setPrinters(context: Context, next: List<SavedPrinter>) {
        synchronized(lock) {
            val keep = next.map { it.address }.toSet()

            sockets.keys.filter { it !in keep }.forEach { address -> closeLocked(address) }

            printers = next
            AgentConfig.savePrinters(context, next)
        }
    }

    fun isConnected(address: String): Boolean =
        synchronized(lock) { sockets[address]?.isConnected == true }

    @SuppressLint("MissingPermission")
    fun send(printerName: String, data: ByteArray) {
        synchronized(lock) {
            val target = printers.firstOrNull {
                it.name.equals(printerName, ignoreCase = true) ||
                    it.address.equals(printerName, ignoreCase = true)
            } ?: printers.singleOrNull()
            ?: throw IOException("No hay impresora configurada para \"$printerName\"")

            try {
                writeToLocked(target, data)
            } catch (first: Exception) {
                closeLocked(target.address)

                try {
                    writeToLocked(target, data)
                } catch (_: Exception) {
                    throw IOException("No se pudo conectar con \"${target.name}\": ${first.message}")
                }
            }
        }
    }

    fun disconnect() {
        synchronized(lock) {
            sockets.keys.forEach { address -> closeLocked(address) }
        }
    }

    @SuppressLint("MissingPermission")
    private fun writeToLocked(target: SavedPrinter, data: ByteArray) {
        var socket = sockets[target.address]

        if (socket?.isConnected != true) {
            closeLocked(target.address)

            val adapter = BluetoothAdapter.getDefaultAdapter()
                ?: throw IOException("Este dispositivo no tiene Bluetooth")

            if (!adapter.isEnabled) {
                throw IOException("El Bluetooth está apagado")
            }

            adapter.cancelDiscovery()

            val device = adapter.getRemoteDevice(target.address)
            val created = device.createRfcommSocketToServiceRecord(SPP_UUID)
            created.connect()
            sockets[target.address] = created
            socket = created
        }

        val ready = socket ?: throw IOException("No se pudo abrir la impresora \"${target.name}\"")

        ready.outputStream.write(data)
        ready.outputStream.flush()
    }

    private fun closeLocked(address: String) {
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
}
