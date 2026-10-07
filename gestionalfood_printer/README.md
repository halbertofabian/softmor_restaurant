# GestionalFood Printer (agente Android)

App Android que imprime tickets térmicos por Bluetooth para la PWA de GestionalFood.
No se configura nada a mano: se vincula con la PWA con un toque desde el botón
**Vincular con GestionalFood**.

## Flujo

1. Se instala desde la PWA: Impresoras → GestionalFood Printer → Descargar agente (APK).
2. Se abre la app, se conceden permisos y se elige la impresora emparejada en Android.
3. Se toca **Vincular con GestionalFood**: se abre la PWA con los datos del agente
   embebidos (token + puerto interno) y queda lista para imprimir.
4. El agente queda como servicio en segundo plano: reconecta solo a la impresora y
   sigue funcionando aunque se cierre la PWA o se reinicie el equipo.

## API local (solo 127.0.0.1)

| Método | Ruta | Token | Descripción |
| --- | --- | --- | --- |
| GET | `/api/health` | No | Estado básico (para detectar el agente) |
| GET | `/api/agent/status` | Sí | Estado + impresoras emparejadas |
| GET | `/api/printer/list` | Sí | Nombres de impresoras emparejadas |
| POST | `/api/agent/configure` | Sí | `{ "address": "...", "name": "..." }` |
| POST | `/api/printer/raw` | Sí | Ticket JSON (comanda / pre-cuenta) |

El token se genera en el primer arranque y viaja únicamente en el enlace de vinculación
(`gf_agent`). Todas las peticiones llevan la cabecera `X-GF-Token`.

## Compilar

Requisitos: JDK 17 y Android SDK 34.

```bash
cd gestionalfood_printer
./gradlew assembleDebug
```

APK: `app/build/outputs/apk/debug/app-debug.apk`

Cópialo a la PWA como `pwa/public/GestionalFoodPrinter.apk` para que el botón
"Descargar agente" lo sirva.

Para pruebas locales puedes cambiar la URL de la PWA en `AgentConfig.PWA_URL`
(por defecto `https://gestionalfood.com/pwa/`).
