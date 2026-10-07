package com.softmor.gestionalfoodprinter

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.Service
import android.content.Intent
import android.content.pm.ServiceInfo
import android.os.Build
import android.os.IBinder
import kotlin.concurrent.thread

class AgentService : Service() {
    private var server: AgentHttpServer? = null

    override fun onCreate() {
        super.onCreate()

        startForegroundCompat()
        BluetoothPrinter.restore(this)

        val http = AgentHttpServer(this)
        http.start()
        server = http

        startReconnectLoop()
    }

    private fun startReconnectLoop() {
        thread(name = "gestionalfood-printer-reconnect", isDaemon = true) {
            while (true) {
                try {
                    BluetoothPrinter.configuredPrinters().forEach { printer ->
                        BluetoothPrinter.ensureConnected(printer.address)
                    }
                } catch (_: Exception) {
                }

                try {
                    Thread.sleep(20_000)
                } catch (_: InterruptedException) {
                    return@thread
                }
            }
        }
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int = START_STICKY

    override fun onDestroy() {
        server?.stop()
        server = null
        super.onDestroy()
    }

    override fun onBind(intent: Intent?): IBinder? = null

    private fun startForegroundCompat() {
        val channelId = "gestionalfood_printer"

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val manager = getSystemService(NotificationManager::class.java)
            val channel = NotificationChannel(
                channelId,
                "GestionalFood Printer",
                NotificationManager.IMPORTANCE_LOW
            )
            manager.createNotificationChannel(channel)
        }

        val notification = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            Notification.Builder(this, channelId)
                .setContentTitle("GestionalFood Printer activo")
                .setContentText("Listo para imprimir")
                .setSmallIcon(android.R.drawable.ic_menu_edit)
                .setOngoing(true)
                .build()
        } else {
            @Suppress("DEPRECATION")
            Notification.Builder(this)
                .setContentTitle("GestionalFood Printer activo")
                .setContentText("Listo para imprimir")
                .setSmallIcon(android.R.drawable.ic_menu_edit)
                .setOngoing(true)
                .build()
        }

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            startForeground(1, notification, ServiceInfo.FOREGROUND_SERVICE_TYPE_CONNECTED_DEVICE)
        } else {
            startForeground(1, notification)
        }
    }
}
