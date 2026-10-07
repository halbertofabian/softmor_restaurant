package com.softmor.gestionalfoodprinter

import android.Manifest
import android.annotation.SuppressLint
import android.app.Activity
import android.app.AlertDialog
import android.bluetooth.BluetoothAdapter
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Color
import android.graphics.PorterDuff
import android.graphics.drawable.GradientDrawable
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.text.InputType
import android.view.Gravity
import android.view.View
import android.view.ViewGroup
import android.widget.Button
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.ScrollView
import android.widget.TextView
import android.widget.Toast

class MainActivity : Activity() {
    private val handler = Handler(Looper.getMainLooper())
    private lateinit var statusValue: TextView
    private lateinit var statusHint: TextView
    private lateinit var pairButton: Button
    private lateinit var configuredContainer: LinearLayout
    private lateinit var devicesContainer: LinearLayout
    private var lastSignature = ""

    private val colorInk = Color.parseColor("#09090B")
    private val colorCard = Color.parseColor("#18181B")
    private val colorCardSoft = Color.parseColor("#27272A")
    private val colorPrimary = Color.parseColor("#E59A1A")
    private val colorText = Color.parseColor("#FAFAFA")
    private val colorGray400 = Color.parseColor("#A1A1AA")
    private val colorGray500 = Color.parseColor("#71717A")
    private val colorBorder = Color.parseColor("#1AFFFFFF")
    private val colorBorderSoft = Color.parseColor("#0DFFFFFF")
    private val colorSuccess = Color.parseColor("#34D399")
    private val colorSuccessBg = Color.parseColor("#1A10B981")
    private val colorSuccessBorder = Color.parseColor("#4D10B981")
    private val colorDanger = Color.parseColor("#F87171")
    private val colorDangerBg = Color.parseColor("#1AF87171")
    private val colorDangerBorder = Color.parseColor("#4DF87171")

    private val refresh = object : Runnable {
        override fun run() {
            renderStatus()
            renderLists()
            handler.postDelayed(this, 2_000)
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        window.statusBarColor = colorInk
        window.navigationBarColor = colorInk

        requestRuntimePermissions()
        BluetoothPrinter.restore(this)
        startAgent()

        setContentView(buildContentView())
    }

    override fun onResume() {
        super.onResume()
        handler.post(refresh)
    }

    override fun onPause() {
        handler.removeCallbacks(refresh)
        super.onPause()
    }

    private fun dp(value: Int): Int = (value * resources.displayMetrics.density).toInt()

    private fun rounded(
        color: Int,
        radiusDp: Int,
        strokeColor: Int = 0,
        strokeDp: Int = 0,
    ): GradientDrawable {
        val shape = GradientDrawable()
        shape.setColor(color)
        shape.cornerRadius = dp(radiusDp).toFloat()

        if (strokeColor != 0 && strokeDp > 0) {
            shape.setStroke(dp(strokeDp), strokeColor)
        }

        return shape
    }

    private fun buildContentView(): ScrollView {
        val scroll = ScrollView(this)
        scroll.setBackgroundColor(colorInk)

        val root = LinearLayout(this)
        root.orientation = LinearLayout.VERTICAL
        root.setPadding(dp(20), dp(24), dp(20), dp(32))
        scroll.addView(root)

        root.addView(buildHeader())
        root.addView(buildStatusCard())
        root.addView(buildPairButton())

        root.addView(sectionTitle("IMPRESORAS CONFIGURADAS"))
        val configuredCard = card()
        configuredContainer = LinearLayout(this)
        configuredContainer.orientation = LinearLayout.VERTICAL
        configuredCard.addView(configuredContainer)
        root.addView(configuredCard)
        root.addView(
            hint("Mantén presionada una impresora para cambiarle el nombre (Barra, Cocina…).")
        )

        root.addView(sectionTitle("IMPRESORAS EMPAREJADAS EN ANDROID"))
        val devicesCard = card()
        devicesContainer = LinearLayout(this)
        devicesContainer.orientation = LinearLayout.VERTICAL
        devicesCard.addView(devicesContainer)
        root.addView(devicesCard)

        root.addView(hint("Versión ${AgentConfig.VERSION}"))

        return scroll
    }

    private fun buildHeader(): View {
        val header = LinearLayout(this)
        header.orientation = LinearLayout.HORIZONTAL
        header.gravity = Gravity.CENTER_VERTICAL
        header.setPadding(0, 0, 0, dp(20))

        val iconBox = LinearLayout(this)
        iconBox.gravity = Gravity.CENTER
        iconBox.background = rounded(Color.parseColor("#1AE59A1A"), 14, Color.parseColor("#33E59A1A"), 1)

        val icon = resources.getDrawable(R.drawable.ic_launcher_printer, theme)
        icon.setColorFilter(colorPrimary, PorterDuff.Mode.SRC_IN)

        val iconView = android.widget.ImageView(this)
        iconView.setImageDrawable(icon)
        iconBox.addView(iconView, LinearLayout.LayoutParams(dp(24), dp(24)))
        header.addView(iconBox, LinearLayout.LayoutParams(dp(44), dp(44)))

        val texts = LinearLayout(this)
        texts.orientation = LinearLayout.VERTICAL
        texts.setPadding(dp(12), 0, 0, 0)

        val title = TextView(this)
        title.text = "GestionalFood Printer"
        title.setTextColor(colorText)
        title.textSize = 19f
        title.setTypeface(null, android.graphics.Typeface.BOLD)
        texts.addView(title)

        val subtitle = TextView(this)
        subtitle.text = "Impresión local por Bluetooth"
        subtitle.setTextColor(colorGray500)
        subtitle.textSize = 12f
        texts.addView(subtitle)

        header.addView(texts)

        return header
    }

    private fun card(): LinearLayout {
        val view = LinearLayout(this)
        view.orientation = LinearLayout.VERTICAL
        view.background = rounded(colorCard, 22, colorBorder, 1)
        view.setPadding(dp(16), dp(16), dp(16), dp(16))

        val params = LinearLayout.LayoutParams(
            ViewGroup.LayoutParams.MATCH_PARENT,
            ViewGroup.LayoutParams.WRAP_CONTENT,
        )
        params.bottomMargin = dp(8)
        view.layoutParams = params

        return view
    }

    private fun buildStatusCard(): View {
        val card = card()

        val label = TextView(this)
        label.text = "ESTADO"
        label.setTextColor(colorGray500)
        label.textSize = 10f
        label.letterSpacing = 0.1f
        card.addView(label)

        statusValue = TextView(this)
        statusValue.textSize = 16f
        statusValue.setTypeface(null, android.graphics.Typeface.BOLD)
        statusValue.setPadding(0, dp(6), 0, 0)
        card.addView(statusValue)

        statusHint = TextView(this)
        statusHint.setTextColor(colorGray500)
        statusHint.textSize = 12f
        statusHint.setPadding(0, dp(2), 0, 0)
        card.addView(statusHint)

        return card
    }

    private fun buildPairButton(): Button {
        pairButton = Button(this)
        pairButton.text = "Vincular con GestionalFood"
        pairButton.isAllCaps = false
        pairButton.setTextColor(Color.parseColor("#000000"))
        pairButton.textSize = 14f
        pairButton.setTypeface(null, android.graphics.Typeface.BOLD)
        pairButton.background = rounded(colorPrimary, 14)
        pairButton.setPadding(dp(16), dp(12), dp(16), dp(12))
        pairButton.setOnClickListener { openPairingLink() }

        val params = LinearLayout.LayoutParams(
            ViewGroup.LayoutParams.MATCH_PARENT,
            ViewGroup.LayoutParams.WRAP_CONTENT,
        )
        params.bottomMargin = dp(8)
        pairButton.layoutParams = params

        return pairButton
    }

    private fun sectionTitle(text: String): TextView {
        val view = TextView(this)
        view.text = text
        view.setTextColor(colorGray500)
        view.textSize = 11f
        view.letterSpacing = 0.08f
        view.setPadding(dp(4), dp(18), 0, dp(8))

        return view
    }

    private fun hint(text: String): TextView {
        val view = TextView(this)
        view.text = text
        view.setTextColor(colorGray500)
        view.textSize = 11f
        view.setPadding(dp(4), dp(8), dp(4), 0)

        return view
    }

    private fun divider(): View {
        val view = View(this)
        view.setBackgroundColor(colorBorderSoft)

        val params = LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, dp(1))
        params.topMargin = dp(10)
        params.bottomMargin = dp(10)
        view.layoutParams = params

        return view
    }

    private fun badge(text: String, success: Boolean): TextView {
        val view = TextView(this)
        view.text = text
        view.textSize = 11f
        view.setTypeface(null, android.graphics.Typeface.BOLD)
        view.setPadding(dp(10), dp(4), dp(10), dp(4))
        view.background = rounded(
            if (success) colorSuccessBg else Color.parseColor("#0DFFFFFF"),
            20,
            if (success) colorSuccessBorder else colorBorder,
            1,
        )
        view.setTextColor(if (success) colorSuccess else colorGray400)

        return view
    }

    private fun secondaryButton(text: String, onClick: () -> Unit): Button {
        val button = Button(this)
        button.text = text
        button.isAllCaps = false
        button.setTextColor(colorGray400)
        button.textSize = 12f
        button.background = rounded(Color.TRANSPARENT, 12, colorBorder, 1)
        button.setPadding(dp(14), dp(6), dp(14), dp(6))
        button.setOnClickListener { onClick() }

        return button
    }

    private fun dangerButton(text: String, onClick: () -> Unit): Button {
        val button = Button(this)
        button.text = text
        button.isAllCaps = false
        button.setTextColor(colorDanger)
        button.textSize = 12f
        button.background = rounded(colorDangerBg, 12, colorDangerBorder, 1)
        button.setPadding(dp(14), dp(6), dp(14), dp(6))
        button.setOnClickListener { onClick() }

        return button
    }

    private fun buildRow(
        name: String,
        secondary: String,
        trailing: View,
    ): View {
        val row = LinearLayout(this)
        row.orientation = LinearLayout.HORIZONTAL
        row.gravity = Gravity.CENTER_VERTICAL
        row.setPadding(0, dp(10), 0, dp(10))

        val texts = LinearLayout(this)
        texts.orientation = LinearLayout.VERTICAL

        val nameView = TextView(this)
        nameView.text = name
        nameView.setTextColor(colorText)
        nameView.textSize = 14f
        nameView.setTypeface(null, android.graphics.Typeface.BOLD)
        texts.addView(nameView)

        if (secondary.isNotBlank()) {
            val secondaryView = TextView(this)
            secondaryView.text = secondary
            secondaryView.setTextColor(colorGray500)
            secondaryView.textSize = 11f
            texts.addView(secondaryView)
        }

        row.addView(
            texts,
            LinearLayout.LayoutParams(0, ViewGroup.LayoutParams.WRAP_CONTENT, 1f),
        )
        row.addView(trailing)

        return row
    }

    private fun startAgent() {
        val intent = Intent(this, AgentService::class.java)

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            startForegroundService(intent)
        } else {
            startService(intent)
        }
    }

    private fun openPairingLink() {
        if (BluetoothPrinter.configuredPrinters().isEmpty()) {
            Toast.makeText(this, "Elige al menos una impresora de la lista.", Toast.LENGTH_SHORT).show()
            return
        }

        startAgent()

        val url = PairingLink.build(this, AgentConfig.port(this))

        try {
            startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url)))
        } catch (_: Exception) {
            Toast.makeText(this, "No se pudo abrir GestionalFood.", Toast.LENGTH_SHORT).show()
        }
    }

    private fun renderStatus() {
        val printers = BluetoothPrinter.configuredPrinters()
        val connected = printers.count { BluetoothPrinter.isConnected(it.address) }

        when {
            printers.isEmpty() -> {
                statusValue.text = "Sin impresoras"
                statusValue.setTextColor(colorGray400)
                statusHint.text = "Agrega una impresora emparejada para comenzar"
            }

            connected == printers.size -> {
                statusValue.text = if (printers.size == 1) "Conectada" else "Todas conectadas"
                statusValue.setTextColor(colorSuccess)
                statusHint.text = "$connected de ${printers.size} impresora(s) listas"
            }

            else -> {
                statusValue.text = "Reconectando…"
                statusValue.setTextColor(colorPrimary)
                statusHint.text = "$connected de ${printers.size} impresora(s) conectadas"
            }
        }

        pairButton.alpha = if (printers.isEmpty()) 0.5f else 1f
    }

    @SuppressLint("MissingPermission")
    private fun renderLists() {
        val configured = BluetoothPrinter.configuredPrinters()
        val bonded = listBonded()
        val signature = configured.joinToString(",") { "${it.name}@${it.address}" } +
            "|" + bonded.joinToString(",") { it.second }

        if (signature == lastSignature) {
            return
        }

        lastSignature = signature

        configuredContainer.removeAllViews()
        devicesContainer.removeAllViews()

        if (configured.isEmpty()) {
            val empty = TextView(this)
            empty.text = "Aún no has agregado impresoras."
            empty.setTextColor(colorGray400)
            empty.textSize = 13f
            configuredContainer.addView(empty)
        }

        configured.forEachIndexed { index, printer ->
            if (index > 0) {
                configuredContainer.addView(divider())
            }

            val connected = BluetoothPrinter.isConnected(printer.address)
            val actions = LinearLayout(this)
            actions.orientation = LinearLayout.HORIZONTAL
            actions.gravity = Gravity.CENTER_VERTICAL
            actions.addView(badge(if (connected) "Conectada" else "Desconectada", connected))

            val remove = dangerButton("Quitar") { removePrinter(printer) }
            val removeParams = LinearLayout.LayoutParams(
                ViewGroup.LayoutParams.WRAP_CONTENT,
                ViewGroup.LayoutParams.WRAP_CONTENT,
            )
            removeParams.leftMargin = dp(8)
            actions.addView(remove, removeParams)

            val row = buildRow(printer.name, printer.address, actions)
            row.setOnLongClickListener {
                renamePrinter(printer)
                true
            }
            configuredContainer.addView(row)
        }

        if (bonded.isEmpty()) {
            val empty = TextView(this)
            empty.text = "No hay impresoras emparejadas. Emparéjalas en Ajustes → Bluetooth."
            empty.setTextColor(colorGray400)
            empty.textSize = 13f
            devicesContainer.addView(empty)

            return
        }

        bonded.forEachIndexed { index, device ->
            if (index > 0) {
                devicesContainer.addView(divider())
            }

            val name = device.first
            val address = device.second
            val isConfigured = configured.any { it.address == address }

            val trailing: View = if (isConfigured) {
                badge("Agregada", false)
            } else {
                secondaryButton("Agregar") { togglePrinter(name, address) }
            }

            devicesContainer.addView(buildRow(name, address, trailing))
        }
    }

    private fun togglePrinter(name: String, address: String) {
        val current = BluetoothPrinter.configuredPrinters().toMutableList()
        val existing = current.firstOrNull { it.address == address }

        if (existing != null) {
            current.remove(existing)
        } else {
            current.add(SavedPrinter(name, address))
        }

        BluetoothPrinter.setPrinters(this, current)
        renderLists()
    }

    private fun removePrinter(printer: SavedPrinter) {
        val current = BluetoothPrinter.configuredPrinters().filter { it.address != printer.address }

        BluetoothPrinter.setPrinters(this, current)
        renderLists()
    }

    private fun renamePrinter(printer: SavedPrinter) {
        val input = EditText(this)
        input.setText(printer.name)
        input.setSelection(input.text.length)
        input.inputType = InputType.TYPE_CLASS_TEXT
        input.setTextColor(colorText)
        input.setHintTextColor(colorGray500)
        input.setBackgroundColor(Color.TRANSPARENT)

        val container = LinearLayout(this)
        container.setPadding(dp(24), dp(8), dp(24), 0)
        container.addView(input, LinearLayout.LayoutParams(
            ViewGroup.LayoutParams.MATCH_PARENT,
            ViewGroup.LayoutParams.WRAP_CONTENT,
        ))

        AlertDialog.Builder(this)
            .setTitle("Nombre de la impresora")
            .setView(container)
            .setPositiveButton("Guardar") { _, _ ->
                val newName = input.text.toString().trim()

                if (newName.isNotBlank()) {
                    val current = BluetoothPrinter.configuredPrinters().map {
                        if (it.address == printer.address) SavedPrinter(newName, it.address) else it
                    }

                    BluetoothPrinter.setPrinters(this, current)
                    renderLists()
                }
            }
            .setNegativeButton("Cancelar", null)
            .show()
    }

    @SuppressLint("MissingPermission")
    private fun listBonded(): List<Pair<String, String>> {
        val adapter = BluetoothAdapter.getDefaultAdapter() ?: return emptyList()

        if (!hasBluetoothPermission()) {
            return emptyList()
        }

        return try {
            adapter.bondedDevices?.map { device ->
                (device.name ?: device.address) to device.address
            } ?: emptyList()
        } catch (_: SecurityException) {
            emptyList()
        }
    }

    private fun hasBluetoothPermission(): Boolean {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.S) {
            return true
        }

        return checkSelfPermission(Manifest.permission.BLUETOOTH_CONNECT) == PackageManager.PERMISSION_GRANTED
    }

    private fun requestRuntimePermissions() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.S) {
            return
        }

        val permissions = mutableListOf(Manifest.permission.BLUETOOTH_CONNECT)

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            permissions.add(Manifest.permission.POST_NOTIFICATIONS)
        }

        requestPermissions(permissions.toTypedArray(), 1)
    }
}
