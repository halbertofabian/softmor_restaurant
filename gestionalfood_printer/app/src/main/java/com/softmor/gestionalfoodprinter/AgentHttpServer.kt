package com.softmor.gestionalfoodprinter

import android.annotation.SuppressLint
import android.bluetooth.BluetoothAdapter
import android.content.Context
import org.json.JSONArray
import org.json.JSONObject
import java.io.BufferedInputStream
import java.io.BufferedOutputStream
import java.io.InputStream
import java.net.InetAddress
import java.net.ServerSocket
import java.net.Socket
import kotlin.concurrent.thread

class AgentHttpServer(private val context: Context) {
    @Volatile
    private var running = false

    private var serverSocket: ServerSocket? = null

    @Volatile
    var port: Int = AgentConfig.DEFAULT_PORT
        private set

    fun start() {
        if (running) {
            return
        }

        running = true

        thread(name = "gestionalfood-printer-http") {
            try {
                val server = bindServer()
                serverSocket = server
                port = server.localPort
                AgentConfig.savePort(context, port)

                while (running) {
                    val socket = try {
                        server.accept()
                    } catch (_: Exception) {
                        break
                    }

                    thread { handle(socket) }
                }
            } catch (_: Exception) {
            } finally {
                running = false
            }
        }
    }

    fun stop() {
        running = false

        try {
            serverSocket?.close()
        } catch (_: Exception) {
        }

        serverSocket = null
    }

    private fun bindServer(): ServerSocket {
        val loopback = InetAddress.getByName("127.0.0.1")
        var lastError: Exception? = null

        for (offset in 0 until AgentConfig.MAX_PORT_ATTEMPTS) {
            val candidate = AgentConfig.DEFAULT_PORT + offset

            try {
                return ServerSocket(candidate, 50, loopback)
            } catch (error: Exception) {
                lastError = error
            }
        }

        throw lastError ?: IllegalStateException("No hay puerto disponible")
    }

    private fun handle(socket: Socket) {
        try {
            socket.soTimeout = 15_000

            val input = BufferedInputStream(socket.getInputStream())
            val output = BufferedOutputStream(socket.getOutputStream())

            val requestLine = readLine(input) ?: return
            val parts = requestLine.split(" ")

            if (parts.size < 2) {
                return
            }

            val method = parts[0]
            val path = parts[1].substringBefore('?')
            var contentLength = 0
            var tokenHeader: String? = null

            while (true) {
                val line = readLine(input) ?: break

                if (line.isEmpty()) {
                    break
                }

                val separator = line.indexOf(':')

                if (separator > 0) {
                    val headerName = line.substring(0, separator).trim()

                    when {
                        headerName.equals("Content-Length", true) ->
                            contentLength = line.substring(separator + 1).trim().toIntOrNull() ?: 0

                        headerName.equals("X-GF-Token", true) ->
                            tokenHeader = line.substring(separator + 1).trim()
                    }
                }
            }

            val body = if (contentLength > 0) {
                val buffer = ByteArray(contentLength)
                var read = 0

                while (read < contentLength) {
                    val chunk = input.read(buffer, read, contentLength - read)

                    if (chunk <= 0) {
                        break
                    }

                    read += chunk
                }

                String(buffer, 0, read, Charsets.UTF_8)
            } else {
                ""
            }

            val (payload, status) = route(method, path, body, tokenHeader)
            val responseBody = payload.toByteArray(Charsets.UTF_8)
            val reason = if (status == 200) "OK" else "Error"
            val header = buildString {
                append("HTTP/1.1 $status $reason\r\n")
                append("Content-Type: application/json; charset=utf-8\r\n")
                append("Content-Length: ${responseBody.size}\r\n")
                append("Access-Control-Allow-Origin: *\r\n")
                append("Access-Control-Allow-Headers: Content-Type, X-GF-Token\r\n")
                append("Access-Control-Allow-Methods: GET, POST, OPTIONS\r\n")
                append("Access-Control-Allow-Private-Network: true\r\n")
                append("Connection: close\r\n")
                append("\r\n")
            }

            output.write(header.toByteArray(Charsets.UTF_8))
            output.write(responseBody)
            output.flush()
        } catch (_: Exception) {
        } finally {
            try {
                socket.close()
            } catch (_: Exception) {
            }
        }
    }

    private fun readLine(input: InputStream): String? {
        val builder = StringBuilder()

        while (true) {
            val value = input.read()

            if (value == -1) {
                return if (builder.isEmpty()) null else builder.toString()
            }

            if (value == '\n'.code) {
                if (builder.isNotEmpty() && builder.last() == '\r') {
                    builder.deleteCharAt(builder.length - 1)
                }

                return builder.toString()
            }

            builder.append(value.toChar())
        }
    }

    private fun route(
        method: String,
        path: String,
        body: String,
        token: String?,
    ): Pair<String, Int> {
        return try {
            when {
                method == "OPTIONS" -> success() to 200

                method == "GET" && path == "/api/health" -> healthJson().toString() to 200

                !isAuthorized(token) -> unauthorized() to 401

                method == "GET" && (path == "/" || path == "/api/agent/status") ->
                    statusJson().toString() to 200

                method == "GET" && path == "/api/printer/list" -> bondedJson().toString() to 200

                method == "POST" && path == "/api/agent/configure" ->
                    configureSingle(body).toString() to 200

                method == "POST" && path == "/api/agent/printers" ->
                    configurePrinters(body).toString() to 200

                method == "POST" && path == "/api/printer/raw" -> print(body).toString() to 200

                else -> error("Ruta no encontrada") to 404
            }
        } catch (exception: Exception) {
            error(exception.message ?: "Error interno") to 500
        }
    }

    private fun isAuthorized(token: String?): Boolean {
        val expected = AgentConfig.token(context)

        return token != null && token == expected
    }

    private fun healthJson(): JSONObject = JSONObject()
        .put("service", "GestionalFood Printer")
        .put("status", "running")
        .put("version", AgentConfig.VERSION)

    private fun statusJson(): JSONObject {
        val printers = JSONArray()

        BluetoothPrinter.configuredPrinters().forEach { printer ->
            printers.put(
                JSONObject()
                    .put("name", printer.name)
                    .put("address", printer.address)
                    .put("connected", BluetoothPrinter.isConnected(printer.address))
            )
        }

        val devices = JSONArray()

        listBondedDevices().forEach { device ->
            devices.put(JSONObject().put("name", device.first).put("address", device.second))
        }

        val configured = BluetoothPrinter.configuredPrinters()
        val first = configured.firstOrNull()

        return JSONObject()
            .put("service", "GestionalFood Printer")
            .put("status", "running")
            .put("version", AgentConfig.VERSION)
            .put("agent_id", AgentConfig.agentId(context))
            .put("port", port)
            .put("printers", printers)
            .put("devices", devices)
            .put("configured", first != null)
            .put("connected", first != null && BluetoothPrinter.isConnected(first.address))
            .put("printer_name", first?.name ?: JSONObject.NULL)
            .put("printer_address", first?.address ?: JSONObject.NULL)
    }

    private fun bondedJson(): JSONObject {
        val names = JSONArray()

        listBondedDevices().forEach { device -> names.put(device.first) }

        return JSONObject().put("status", "success").put("printers", names)
    }

    private fun configureSingle(body: String): JSONObject {
        val json = JSONObject(body)
        val address = json.optString("address", "")

        if (address.isBlank()) {
            throw IllegalArgumentException("Falta la dirección Bluetooth de la impresora")
        }

        val device = listBondedDevices().firstOrNull { it.second.equals(address, ignoreCase = true) }
            ?: throw IllegalArgumentException("La impresora no está emparejada en Android")

        val requestedName = json.optString("name", "").trim().ifBlank { device.first }
        val current = BluetoothPrinter.configuredPrinters().toMutableList()
        val existing = current.firstOrNull { it.address.equals(device.second, ignoreCase = true) }

        if (existing != null) {
            current[current.indexOf(existing)] = SavedPrinter(requestedName, device.second)
        } else {
            current.add(SavedPrinter(requestedName, device.second))
        }

        BluetoothPrinter.setPrinters(context, current)

        return JSONObject().put("status", "success")
    }

    private fun configurePrinters(body: String): JSONObject {
        val json = JSONObject(body)
        val list = json.optJSONArray("printers") ?: JSONArray()
        val bonded = listBondedDevices()
        val result = mutableListOf<SavedPrinter>()

        for (index in 0 until list.length()) {
            val item = list.optJSONObject(index) ?: continue
            val address = item.optString("address", "")

            if (address.isBlank()) {
                continue
            }

            val device = bonded.firstOrNull { it.second.equals(address, ignoreCase = true) }
                ?: throw IllegalArgumentException("La impresora $address no está emparejada en Android")

            val requestedName = item.optString("name", "").trim()
            result.add(SavedPrinter(requestedName.ifBlank { device.first }, device.second))
        }

        BluetoothPrinter.setPrinters(context, result)

        return JSONObject().put("status", "success")
    }

    private fun print(body: String): JSONObject {
        val json = JSONObject(body)
        val printerName = json.optString("printer_name", "")

        BluetoothPrinter.send(printerName, TicketFormatter.buildBytes(json))

        return JSONObject().put("status", "success").put("message", "Printed successfully")
    }

    @SuppressLint("MissingPermission")
    private fun listBondedDevices(): List<Pair<String, String>> {
        val adapter = BluetoothAdapter.getDefaultAdapter() ?: return emptyList()

        return try {
            adapter.bondedDevices?.map { device ->
                (device.name ?: device.address) to device.address
            } ?: emptyList()
        } catch (_: SecurityException) {
            emptyList()
        }
    }

    private fun success(): String = JSONObject().put("status", "success").toString()

    private fun unauthorized(): String =
        JSONObject().put("status", "error").put("message", "No autorizado").toString()

    private fun error(message: String): String =
        JSONObject().put("status", "error").put("message", message).toString()
}
