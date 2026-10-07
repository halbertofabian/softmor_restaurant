package com.softmor.gestionalfoodprinter

import android.content.Context
import android.util.Base64
import org.json.JSONObject

object PairingLink {
    const val PARAM = "gf_agent"

    fun build(context: Context, port: Int): String {
        val payload = JSONObject()
            .put("v", 1)
            .put("port", port)
            .put("token", AgentConfig.token(context))
            .put("id", AgentConfig.agentId(context))
            .put("name", "GestionalFood Printer")
            .toString()

        val encoded = Base64.encodeToString(
            payload.toByteArray(Charsets.UTF_8),
            Base64.URL_SAFE or Base64.NO_WRAP or Base64.NO_PADDING,
        )

        return "${AgentConfig.PWA_URL}?$PARAM=$encoded"
    }
}
