package com.softmor.gestionalfoodprinter

import android.content.Context
import org.json.JSONArray
import org.json.JSONObject
import java.util.UUID

data class SavedPrinter(val name: String, val address: String)

object AgentConfig {
    const val VERSION = "1.3.0"
    const val PWA_URL = "https://gestionalfood.com/pwa/"
    const val DEFAULT_PORT = 8123
    const val MAX_PORT_ATTEMPTS = 6

    private const val PREFS = "gestionalfood_printer"
    private const val KEY_TOKEN = "token"
    private const val KEY_AGENT_ID = "agent_id"
    private const val KEY_PORT = "port"
    private const val KEY_PRINTERS = "printers_json"

    private fun prefs(context: Context) =
        context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)

    fun token(context: Context): String {
        val existing = prefs(context).getString(KEY_TOKEN, null)

        if (existing != null) {
            return existing
        }

        val created = UUID.randomUUID().toString().replace("-", "") +
            UUID.randomUUID().toString().replace("-", "")

        prefs(context).edit().putString(KEY_TOKEN, created).apply()

        return created
    }

    fun agentId(context: Context): String {
        val existing = prefs(context).getString(KEY_AGENT_ID, null)

        if (existing != null) {
            return existing
        }

        val created = UUID.randomUUID().toString()
        prefs(context).edit().putString(KEY_AGENT_ID, created).apply()

        return created
    }

    fun port(context: Context): Int = prefs(context).getInt(KEY_PORT, DEFAULT_PORT)

    fun savePort(context: Context, port: Int) {
        prefs(context).edit().putInt(KEY_PORT, port).apply()
    }

    fun printers(context: Context): List<SavedPrinter> {
        val raw = prefs(context).getString(KEY_PRINTERS, null) ?: return emptyList()

        return try {
            val array = JSONArray(raw)
            val result = mutableListOf<SavedPrinter>()

            for (index in 0 until array.length()) {
                val item = array.optJSONObject(index) ?: continue
                val name = item.optString("name", "")
                val address = item.optString("address", "")

                if (name.isNotBlank() && address.isNotBlank()) {
                    result.add(SavedPrinter(name, address))
                }
            }

            result
        } catch (_: Exception) {
            emptyList()
        }
    }

    fun savePrinters(context: Context, printers: List<SavedPrinter>) {
        val array = JSONArray()

        printers.forEach { printer ->
            array.put(JSONObject().put("name", printer.name).put("address", printer.address))
        }

        prefs(context).edit().putString(KEY_PRINTERS, array.toString()).apply()
    }
}
