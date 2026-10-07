package com.softmor.gestionalfoodprinter

import org.json.JSONArray
import org.json.JSONObject
import java.nio.charset.Charset
import java.util.Calendar
import java.util.Date
import java.util.Locale

object TicketFormatter {
    private val textCharset: Charset = try {
        Charset.forName("Cp858")
    } catch (_: Exception) {
        Charset.forName("ISO-8859-1")
    }

    private val monthNames = arrayOf(
        "Ene", "Feb", "Mar", "Abr", "May", "Jun",
        "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"
    )

    private fun formatDate(date: Date): String {
        val calendar = Calendar.getInstance().apply { time = date }
        val day = String.format(Locale.US, "%02d", calendar.get(Calendar.DAY_OF_MONTH))
        val month = monthNames[calendar.get(Calendar.MONTH)]
        val year = calendar.get(Calendar.YEAR)

        return "$day/$month/$year ${formatTime(date)}"
    }

    private fun formatTime(date: Date): String {
        val calendar = Calendar.getInstance().apply { time = date }
        val hour = calendar.get(Calendar.HOUR).let { if (it == 0) 12 else it }
        val minutes = String.format(Locale.US, "%02d", calendar.get(Calendar.MINUTE))
        val meridiem = if (calendar.get(Calendar.AM_PM) == Calendar.PM) "PM" else "AM"

        return "${String.format(Locale.US, "%02d", hour)}:$minutes $meridiem"
    }

    fun buildBytes(payload: JSONObject): ByteArray {
        val text = buildText(payload)
        val encoded = textCharset.encode(text)
        val body = ByteArray(encoded.remaining())
        encoded.get(body)

        val output = ByteArray(body.size + 5)
        output[0] = 0x1B
        output[1] = '@'.code.toByte()
        output[2] = 0x1B
        output[3] = 't'.code.toByte()
        output[4] = 19
        System.arraycopy(body, 0, output, 5, body.size)

        return output
    }

    private fun buildText(payload: JSONObject): String {
        val type = payload.optString("type", "kitchen")
        val isKitchen = type.equals("kitchen", ignoreCase = true)
        val isPreCheck = type.equals("pre_check", ignoreCase = true)
        val sb = StringBuilder()

        if (isKitchen) {
            val areaName = payload.optString("area_name", "")

            if (areaName.isNotBlank()) {
                sb.appendLine(areaName.uppercase(Locale.getDefault()))
            }

            sb.appendLine("**************")
            sb.appendLine(" MESA ${payload.optString("table_name", "?").uppercase(Locale.getDefault())}")
            sb.appendLine("**************")
            sb.appendLine()

            val items = payload.optJSONArray("items") ?: JSONArray()

            for (index in 0 until items.length()) {
                val item = items.optJSONObject(index) ?: continue
                sb.appendLine("${formatQuantity(item.optDouble("quantity", 1.0))} x ${item.optString("name", "Producto")}")

                val notes = item.optString("notes", "")
                if (notes.isNotBlank()) {
                    sb.appendLine("   * $notes")
                }

                sb.appendLine()
            }

            sb.appendLine("--- ${formatTime(Date())} ---")
            sb.appendLine("Mesero: ${payload.optString("waiter_name", "N/A")}")
            sb.appendLine()
            sb.appendLine()
        } else {
            sb.appendLine(payload.optString("header", "Ticket de Venta"))
            sb.appendLine("Sucursal: ${payload.optString("branch_name", "Principal")}")
            sb.appendLine(
                payload.optString(
                    "date",
                    formatDate(Date())
                )
            )
            sb.appendLine("Ticket #: ${payload.optString("ticket_id", "N/A")}")

            val table = payload.optString("table_name", "")
            if (table.isNotBlank()) {
                sb.appendLine("Mesa: $table")
            }

            val waiter = payload.optString("waiter_name", "")
            if (waiter.isNotBlank()) {
                sb.appendLine("Mesero: $waiter")
            }

            sb.appendLine("--------------------------------")

            val items = payload.optJSONArray("items") ?: JSONArray()

            for (index in 0 until items.length()) {
                val item = items.optJSONObject(index) ?: continue
                val quantity = item.optDouble("quantity", 1.0)
                val price = item.optDouble("price", 0.0)
                sb.appendLine("${formatQuantity(quantity)} x ${item.optString("name", "Producto")}")
                sb.appendLine("$${money(price * quantity)}")
            }

            sb.appendLine("--------------------------------")
            sb.appendLine("TOTAL: $${money(payload.optDouble("total", 0.0))}")

            if (isPreCheck && payload.optBoolean("tips_enabled", false)) {
                val tips = payload.optJSONArray("tip_suggestions") ?: JSONArray()

                if (tips.length() > 0) {
                    sb.appendLine("--------------------------------")
                    sb.appendLine("PROPINA SUGERIDA")

                    for (index in 0 until tips.length()) {
                        val tip = tips.optJSONObject(index) ?: continue
                        sb.appendLine("${formatQuantity(tip.optDouble("percent", 0.0))}%: $${money(tip.optDouble("amount", 0.0))}")
                    }
                }
            }

            val disclaimer = payload.optString("pre_check_disclaimer", "")
            if (isPreCheck && disclaimer.isNotBlank()) {
                sb.appendLine()
                sb.appendLine(disclaimer)
            }

            sb.appendLine()
            sb.appendLine()
        }

        return sb.toString()
    }

    private fun money(value: Double): String = String.format(Locale.US, "%.2f", value)

    private fun formatQuantity(value: Double): String =
        if (value % 1.0 == 0.0) value.toInt().toString() else String.format(Locale.US, "%.2f", value)
}
