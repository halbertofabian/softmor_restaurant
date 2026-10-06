# PWA GestionalFood — Roadmap Técnico (Fase 1)

Documento de trabajo para la nueva PWA conectada al sistema Laravel existente.

## 1. Objetivo y alcance

Desarrollar una PWA en React que consuma el backend actual de GestionalFood y cubra en su primera etapa:

- Inicio de sesión con usuarios existentes.
- Selección de sucursal.
- Consulta de mesas y productos.
- Levantamiento de comandas (abrir mesa, agregar productos/cantidades/notas, enviar).
- Consulta de áreas de preparación (desde backend).
- Configuración **local por dispositivo** de impresoras (IndexedDB).
- Impresión de prueba.
- Impresión de comandas agrupadas por área.
- Reimpresión de comandas.

Fuera de alcance en esta etapa: impresión remota, agentes de impresión nuevos, sincronización de impresoras entre dispositivos, administración completa del restaurante, configuración global de impresoras desde el backend.

## 2. Estado actual del sistema (punto de partida)

| Pieza | Estado | Ubicación |
| :--- | :--- | :--- |
| Laravel 12 / PHP 8.2 / MySQL | OK | `composer.json` |
| Login API con Sanctum (Bearer) | Existe | `routes/api.php:6`, `app/Http/Controllers/Api/AuthController.php` |
| Sucursales | Existe | `GET /api/branches` |
| Mesas (listar/ocupar/liberar) | Existe | `GET /api/tables`, `PUT /api/tables/{id}/occupy`, `release` |
| Productos + categorías + sabores + combos | Existe | `GET /api/products` |
| Comandas | Existe | `POST /api/orders/get-or-create`, `show`, `items`, `send-kitchen` |
| Área de preparación por producto | Existe en BD | `products.preparation_area_id` (FK directa) |
| Snapshot de área e `is_printed` por línea | Existe en BD | `order_details.preparation_area_id`, `order_details.is_printed` |
| Multi-tenant / multi-sucursal | Existe | `tenant_id`, `branch_user`, traits `BelongsToTenant/Branch` |
| Impresión servidor→agente .NET | Existe (se conserva para web) | `print_agents`, `print_jobs`, `PrintJobService`, `print_agent_windows/` |
| PWA / manifest / service worker | No existe | — |
| Endpoint de áreas de preparación | No existe en API | solo datatable web en `routes/web.php:77` |

Reglas del proyecto a respetar (ver `.agent/rules/code-config.md`):

- Toda tabla nueva o modificada debe llevar `tenant_id` y `branch_id` (si aplica), con migraciones `alter table` sobre tablas existentes.
- El aislamiento de datos es multitenancy; los controladores API validan tenant/sucursal manualmente (no hay sesión de branch en Sanctum).

## 3. Arquitectura

```
DISPOSITIVO (móvil / tablet / PC)
┌───────────────────────────────────────────────────────────────┐
│ PWA React (Vite + Workbox)                                    │
│  ├─ TanStack Query  → estado del servidor                     │
│  ├─ Zustand         → estado UI / sesión                      │
│  ├─ Dexie/IndexedDB → impresoras, mapeo área→impresora, outbox│
│  └─ lib/printing    → PrinterTransport (interfaz TS)          │
│      ├─ BluetoothTransport  → Web Bluetooth + ESC/POS         │
│      └─ BridgeTransport     → http://localhost:8000 (opcional)│
└──────────────┬──────────────────────────────┬─────────────────┘
               │ HTTPS + Bearer Sanctum       │ BLE GATT por área
               ▼                              ▼
      Laravel 12 API /api/*            Impresora Cocina (BT1)
      MySQL (tenant_id, branch_id)     Impresora Barra  (BT2)
               │
               └─ print_jobs → Agente .NET (solo web/POS; NO PWA)
```

Principio clave: **dos modos de impresión conviven**.

- Web/POS actual: cola `print_jobs` + agente .NET con `preparation_areas.printer_name`.
- PWA: impresión 100% local; el backend solo entrega áreas y el payload por área. La PWA decide la impresora física en ese dispositivo.

La configuración local del dispositivo **nunca** se envía a Laravel ni depende de la configuración de impresoras del backend.

## 4. Stack de la PWA (React)

| Necesidad | Tecnología |
| :--- | :--- |
| Base | React 19 + Vite + TypeScript |
| Rutas | React Router v7 |
| Estado servidor / caché | TanStack Query |
| Estado UI / sesión | Zustand |
| Formularios y validación | react-hook-form + Zod |
| UI | Tailwind CSS + shadcn/ui (alternativa: react-bootstrap estilo Vuexy) |
| PWA / offline | vite-plugin-pwa (Workbox) |
| IndexedDB | Dexie |
| ESC/POS | `@point-of-sale/receipt-printer-encoder` |
| Bluetooth | Web Bluetooth API |
| HTTP | fetch/axios con interceptor `Authorization: Bearer` |
| Tests | Vitest + React Testing Library + MSW + Playwright |

### Identidad visual

- Color primario: `#E59A1A` (hover `#C98415`, claro `#FFAB1D`), definidos en `pwa/src/index.css` vía `@theme` (`bg-primary`, `text-primary`, etc.).
- Logo: `public/assets/img/icon_gestionalfood.png` (el del login de Laravel), copiado a `pwa/public/gestionalfood.png`; los íconos PWA se generan desde ahí.
- El login replica el estilo de `resources/views/layouts/auth.blade.php`: fondo `#09090b` con grilla, card `#18181b`, tipografía Plus Jakarta Sans y acentos en el primario.

### Estructura de carpetas

```
pwa/
├─ public/
│  ├─ icons/ (192, 512, maskable)
│  └─ offline.html
├─ src/
│  ├─ app/            (router, providers, layout, guards)
│  ├─ features/
│  │  ├─ auth/        (login, selección de sucursal)
│  │  ├─ tables/      (mesas, comanda)
│  │  ├─ orders/      (ítems, enviar, reimpresión)
│  │  └─ printers/    (ajustes, pairing, impresión de prueba)
│  ├─ lib/
│  │  ├─ api/         (cliente, endpoints, tipos)
│  │  ├─ db/          (Dexie: schema y repositorios)
│  │  └─ printing/    (PrinterTransport, BluetoothTransport, BridgeTransport, encoder)
│  ├─ components/ui/
│  └─ main.tsx
└─ vite.config.ts     (vite-plugin-pwa + manifest)
```

### Despliegue

- Build estático servido en `https://<dominio>/pwa` (mismo origen que Laravel, recomendado) o subdominio.
- HTTPS obligatorio (service worker + Web Bluetooth).
- `manifest.webmanifest` con `display: standalone`, íconos 192/512/maskable y `start_url`.
- El service worker solo cachea el shell y assets; los datos van a IndexedDB.

## 5. Backend — Contratos de API (Fase 0)

Todos los endpoints nuevos van bajo `auth:sanctum` (salvo login) y validan tenant/sucursal igual que los controladores `Api\*` existentes.

### 5.1 `POST /api/login` (ajuste)

Request:

```json
{
  "email": "mesero@negocio.com",
  "password": "******",
  "device_name": "Tablet Salon A"
}
```

Response 200:

```json
{
  "token": "12|xxxxxxxx",
  "user": { "id": 5, "name": "Juan Pérez", "email": "mesero@negocio.com", "estado": "activo" },
  "role": "mesero",
  "tenant_id": "9b7e...",
  "branches": [
    { "id": 1, "name": "Sucursal Centro", "is_active": true }
  ]
}
```

Cambios requeridos:

- Rechazar 403 si `users.estado != 'activo'`.
- Eliminar `$user->branch_id` (columna inexistente); devolver `branches` desde el pivote `branch_user`.
- Nombre de token por dispositivo (`pwa-<device_name>`).
- Aplicar `throttle` (p. ej. `throttle:5,1`).

### 5.2 `GET /api/me`

Response 200:

```json
{
  "user": { "id": 5, "name": "Juan Pérez", "email": "mesero@negocio.com" },
  "role": "mesero",
  "tenant_id": "9b7e...",
  "permissions": {
    "take_orders": true,
    "reprint": true,
    "manage_printers": true
  },
  "branches": [
    { "id": 1, "name": "Sucursal Centro", "is_active": true }
  ]
}
```

`permissions` se deriva del rol (`administrador | mesero | caja | cocinero`). No se crea tabla nueva de permisos en esta etapa.

### 5.3 `GET /api/preparation-areas?branch_id=1`

Response 200:

```json
{
  "data": [
    { "id": 1, "name": "Cocina",  "print_ticket": true, "sort_order": 0, "status": true },
    { "id": 2, "name": "Barra",   "print_ticket": true, "sort_order": 1, "status": true },
    { "id": 3, "name": "Postres", "print_ticket": true, "sort_order": 2, "status": true }
  ]
}
```

Nota: `printer_name` del backend puede incluirse como dato informativo, pero la PWA no lo usa para imprimir.

### 5.4 `POST /api/orders/{order}/send`

Modo cliente: marca los `pending` como `sent`, ejecuta el descuento de recetas existente (`RecipeInventoryService::sendPending`) y **no** encola `PrintJob`. Devuelve el payload agrupado por área.

Request:

```json
{
  "print_mode": "client",
  "allow_negative_inventory": false
}
```

Response 200:

```json
{
  "order": { "id": 10, "status": "sent", "total": 45.50 },
  "print": {
    "generated_at": "2026-10-05T14:30:00-05:00",
    "table_name": "Mesa 5",
    "waiter_name": "Juan Pérez",
    "areas": [
      {
        "area_id": 1,
        "area_name": "Cocina",
        "print_ticket": true,
        "detail_ids": [101, 102],
        "items": [
          { "detail_id": 101, "quantity": 2, "name": "Hamburguesa", "notes": "sin cebolla" },
          { "detail_id": 102, "quantity": 1, "name": "Papas", "notes": null }
        ]
      },
      {
        "area_id": 2,
        "area_name": "Barra",
        "print_ticket": true,
        "detail_ids": [103],
        "items": [
          { "detail_id": 103, "quantity": 2, "name": "Café americano", "notes": null }
        ]
      }
    ]
  },
  "inventory_warnings": []
}
```

Errores:

- 409 con `inventory_warnings` si falta stock y `allow_negative_inventory = false` (reutiliza el flujo actual de la API móvil).
- 422 si la orden no tiene ítems `pending`.

Compatibilidad: si `print_mode` es `server` (o ausente), el comportamiento actual se mantiene (encola `PrintJob` y responde como hoy). Así la web/POS no se ve afectada.

### 5.5 `POST /api/orders/{order}/mark-printed`

Request:

```json
{ "detail_ids": [101, 102] }
```

Response 200:

```json
{ "updated": 2 }
```

Idempotente. Solo actualiza líneas de la orden y del tenant.

### 5.6 `GET /api/orders/{order}/print-payload?area_id=1`

Para reimpresión. Devuelve el mismo bloque `print` de 5.4 (sin `area_id`, todas las áreas) y **no** altera estados ni `is_printed`. Si se envía `area_id`, devuelve solo esa área.

### 5.7 `GET /api/orders?status=open&table_id=&per_page=20&page=1`

Listado paginado para historial y reimpresión:

```json
{
  "data": [
    {
      "id": 10,
      "table_id": 3,
      "table_name": "Mesa 5",
      "status": "open",
      "total": 45.50,
      "has_pending": false,
      "waiter_name": "Juan Pérez",
      "created_at": "2026-10-05T14:00:00-05:00"
    }
  ],
  "meta": { "current_page": 1, "last_page": 3, "per_page": 20, "total": 45 }
}
```

### 5.8 Ampliación de `GET /api/orders/{order}` (existente)

Agregar a cada detalle: `preparation_area_id`, `preparation_area_name`, `is_printed`, `parent_order_detail_id`, `is_combo_component`, `flavor_name`. Necesario para pintar la comanda y saber qué falta imprimir.

### 5.9 Infraestructura

- Publicar `config/cors.php` y restringir a los orígenes reales de la PWA.
- Aplicar `throttle:api` al grupo API en `bootstrap/app.php`.
- Middleware/validación: bloquear tokens de usuarios con `estado != 'activo'`.
- (Opcional) expiración de tokens y abilities (`orders:create`, `orders:read`).

## 6. Configuración local de impresoras (IndexedDB)

Base de datos Dexie: `gestionalfood_pwa`.

```ts
printers: {
  id: string;                 // uuid local
  alias: string;              // "BT Cocina 1"
  transport: 'bluetooth' | 'bridge';
  bluetoothDeviceId?: string; // device.id de Web Bluetooth
  gatt?: { service: string; characteristic: string };
  bridgeUrl?: string;         // http://localhost:8000/api/printer/raw
  width: 58 | 80;
  codepage: 858;
  copies: number;
  createdAt: string;
}

areaPrinters: {
  tenant_id: string;
  branch_id: number;
  area_id: number;
  area_name: string;          // snapshot informativo
  printerId: string;
}

settings: {
  tenant_id: string;
  branch_id: number;
  autoPrint: boolean;
  reprintCopies: number;
  deviceUuid: string;         // generado una vez por instalación
}

printOutbox: {
  id: string;
  orderId: number;
  areaId: number;
  payload: object;
  status: 'pending' | 'done' | 'error';
  attempts: number;
  lastError?: string;
  createdAt: string;
}
```

Reglas:

- La configuración **persiste** al cerrar sesión, cerrar la app y reinstalar (mientras el navegador conserve IndexedDB).
- El mapeo se guarda por `tenant_id + branch_id + area_id` para que dos negocios en el mismo dispositivo no se pisen.
- Existe botón "Olvidar configuración de este dispositivo" (borra `printers` y `areaPrinters`).
- `preparation_areas.printer_name` del backend se muestra solo como referencia ("configurado en el sistema").

## 7. Impresión local

### 7.1 Transportes

| Transporte | Soporte | Uso |
| :--- | :--- | :--- |
| Web Bluetooth (GATT + ESC/POS) | Android Chrome, Windows Chrome/Edge | Principal |
| Bridge local `http://localhost:8000/api/printer/raw` | Windows con agente .NET instalado | Fallback opcional |
| WebUSB | Android/Chrome desktop | Fase 2 (opcional) |
| iOS/Safari | No soporta Web Bluetooth | Ver riesgos (sección 12) |

Interfaz común (en `lib/printing`):

```ts
interface PrinterTransport {
  connect(): Promise<void>;
  isConnected(): boolean;
  print(escpos: Uint8Array): Promise<void>;
  disconnect(): Promise<void>;
}
```

`PrinterTransport` es TS puro (sin React) y se inyecta vía factory según la config. Esto permite un `FakePrinterTransport` para tests.

### 7.2 Emparejamiento Bluetooth

1. `navigator.bluetooth.requestDevice({ acceptAllDevices: true, optionalServices: [...] })` con filtro por servicios comunes (0xFFE0, 0xFF00, 0x18F0, Epson 0x49535343).
2. `device.gatt.connect()`, descubrir servicios y localizar una característica escribible.
3. Guardar `device.id` + UUIDs de servicio/característica en `printers`.
4. Escritura en chunks (100–180 bytes) con pausa; `writeWithoutResponse` cuando esté disponible; ESC/POS con corte al final.
5. Reconexión: `navigator.bluetooth.getDevices()` + `gatt.connect()`. Si el navegador exige gesto, mostrar botón "Reconectar impresoras".

Nota: los perfiles GATT varían por marca; el descubrimiento automático de característica escribible evita código por modelo.

### 7.3 Impresión de prueba

- Pantalla Ajustes → por área: botón **Imprimir prueba**.
- Imprime ticket con: alias de impresora, transporte, ancho (58/80), code page, fecha, nombre del dispositivo y un ítem de muestra.
- Resultado: "OK" o error GATT/conexión concreto.

### 7.4 Flujo de comanda con impresión por área

1. La PWA guarda los ítems en Laravel (`/api/orders/*`). La falta de impresora nunca bloquea el guardado.
2. `POST /api/orders/{order}/send` con `print_mode=client`.
3. Laravel responde `print.areas` (5.4).
4. La PWA resuelve `areaPrinters` local y agrupa por área.
5. Por cada área con impresora configurada: imprime (Cocina → BT1, Barra → BT2).
6. Por cada área sin impresora: muestra mensaje:
   `La comanda fue guardada, pero Cocina no tiene una impresora configurada en este dispositivo.`
7. `POST /api/orders/{order}/mark-printed` con los `detail_ids` impresos exitosamente.
8. Si falla una impresión, la comanda sigue guardada y se ofrece "Reintentar impresión" / "Configurar impresora".

### 7.5 Reimpresión

- Desde el detalle de la comanda o desde el historial (`GET /api/orders`).
- `GET /api/orders/{order}/print-payload` (no altera estados).
- Reimprimir área completa o comanda completa.
- Registrar cada reimpresión en `printOutbox` local (auditoría local del dispositivo).
- `is_printed` del backend se mantiene como "impresa al menos una vez" (web/POS); para el dispositivo, la fuente de verdad de "ya imprimí esto" es IndexedDB.

## 8. Pantallas

| # | Pantalla | Contenido |
| :--- | :--- | :--- |
| 1 | Login | Email/contraseña, error claro, estado de carga |
| 2 | Selección de sucursal | Si hay más de una; recuerda la última localmente |
| 3 | Mesas | Por zona (`tables.zone`), estado, comanda activa, botón abrir |
| 4 | Comanda | Productos por categoría, sabor/combo, cantidad, notas, editar/eliminar, total, Enviar |
| 5 | Ajustes → Impresoras | Áreas del backend + selector de impresora + emparejar BT + Imprimir prueba + reconectar + ancho/code page/copias + olvidar configuración |
| 6 | Historial / Reimpresión | Comandas recientes, reimprimir por área o completa |
| 7 | Diagnóstico | Soporte Web Bluetooth, impresoras emparejadas, última impresión, versión SW |

## 9. Offline

- Fase 1: shell de la app + assets en caché (Workbox) y catálogo (mesas/productos/áreas) en IndexedDB con marca de "actualizado hace X".
- Fase 4: **outbox transaccional** con TanStack Query: si no hay red al enviar, la comanda se encola en `printOutbox` (y un buffer de comanda pendiente) y se sincroniza al reconectar.
- No prometer operación multidispositivo 100% offline: el estado de mesa se comparte entre dispositivos y requiere red.

## 10. Seguridad

- Token Sanctum con expiración y abilities; guardado en IndexedDB, nunca la contraseña.
- `throttle` en login; bloqueo de usuarios inactivos.
- CORS restringido al origen de la PWA.
- HTTPS obligatorio.
- `POST /api/printer/raw` se mantiene solo como bridge local (no exponerlo a Internet).
- Sanitizar siempre el contenido de notas antes de ESC/POS (evitar inyección de comandos de control).

## 11. Fases de implementación

### Fase 0 — Backend (habilitadores) — COMPLETADA

- [x] `GET /api/preparation-areas`
- [x] `GET /api/me`
- [x] `POST /api/orders/{order}/send` con `print_mode=client`
- [x] `POST /api/orders/{order}/mark-printed`
- [x] `GET /api/orders/{order}/print-payload`
- [x] `GET /api/orders` (listado paginado)
- [x] Ampliar `GET /api/orders/{order}` con área/estado por detalle
- [x] Ajustes login: `estado`, branches, token por dispositivo, throttle
- [x] `config/cors.php` + `throttle:api`
- [x] Tests feature de cada endpoint (18 tests, 93 aserciones)

### Fase 1 — PWA base — COMPLETADA

- [x] Proyecto React + Vite + TS + Tailwind (carpeta `pwa/`)
- [x] vite-plugin-pwa + manifest + íconos + shell offline (banner de conexión)
- [x] Cliente API con Bearer y manejo de 401 (`src/lib/api/client.ts`)
- [x] Login + selección de sucursal + guards de ruta
- [x] Layout mobile-first + navegación inferior

### Fase 2 — Mesas y comanda — COMPLETADA

- [x] Lista de mesas por zona y estados (`GET /api/tables` ahora incluye `zone`)
- [x] Abrir/ocupar mesa y recuperar comanda activa (`get-or-create`)
- [x] Catálogo de productos con categorías, sabores y combos (combos muestran sus componentes)
- [x] Agregar/editar/eliminar ítems con notas y cantidades (`PATCH /api/orders/{order}/items/{detail}` nuevo)
- [x] Enviar comanda sin imprimir (`send` con `print_mode=client`; el payload de impresión se usará en Fase 3) y refrescar total

Notas: los combos se agregan y eliminan; su cantidad se edita eliminando y volviendo a agregar. El 409 de inventario muestra diálogo para confirmar stock negativo.

Asignación de mesero (igual que la web): el mesero se auto-asigna al abrir la mesa; admin/caja deben elegir un mesero de la sucursal (`GET /api/waiters`). Un mesero no puede abrir, editar ni desocupar comandas de otro mesero. El botón "Desocupar" solo aparece cuando la comanda no tiene productos activos; al desocupar se cierran las órdenes abiertas y la mesa queda libre (`PUT /api/tables/{table}/release`).

### Fase 3 — Impresión local — COMPLETADA

- [x] Dexie: schema y repositorios (`src/lib/db/`)
- [x] Pantalla Ajustes → Impresoras en `/impresoras` (áreas desde backend + mapeo local por `tenant+branch+área`)
- [x] `PrinterTransport` + `BluetoothTransport` + encoder ESC/POS (`@point-of-sale/receipt-printer-encoder`, code page 858, 58/80mm)
- [x] Emparejamiento BLE (descubrimiento de característica escribible), reconexión con `getDevices()` y `BridgeTransport` opcional contra el agente .NET (`/api/printer/list` y `/api/printer/raw`)
- [x] Imprimir prueba por impresora y por área
- [x] Impresión por área al enviar comanda + `mark-printed` para las áreas impresas
- [x] Mensajes de área sin impresora configurada y errores de conexión

Notas: preferencia local `autoPrint` (si se desactiva, se ofrece imprimir tras guardar); ancho de papel y copias por impresora; la configuración vive solo en IndexedDB del dispositivo. Web Bluetooth no existe en iOS/Safari: en ese caso usar bridge local o impresora de red.

Resiliencia de conexión BLE: keep-alive cada 60 s (y al volver visible/online) que reconecta impresoras caídas; el botón "Probar" abre el selector automáticamente si el permiso se perdió (sin pasar por Ajustes); reconexión por nombre si el `device.id` cambia entre sesiones; se solicita almacenamiento persistente (`navigator.storage.persist`) para no perder IndexedDB.

### Fase 4 — Reimpresión, offline y QA — COMPLETADA (QA en dispositivo en curso)

- [x] Historial y reimpresión por área/completa (`/historial`, `ReprintSheet`, botón de reimpresión en la comanda)
- [x] Outbox y sincronización al reconectar (envíos encolados en IndexedDB; flush al volver online, cada 30 s y banner de pendientes)
- [x] Auditoría local de impresiones (`printLogs` en Dexie + tarjeta "Actividad reciente" en Impresoras)
- [x] Tests unitarios con Vitest (13 pruebas: encoder ESC/POS, servicios GATT y formato). MSW/RTL/Playwright quedan como mejora
- [ ] Pruebas en dispositivos e impresoras reales (en curso: Edge + impresora BLE)
- [x] Performance: code splitting por ruta y carga diferida del stack de impresión (chunk principal < 500 kB)

### Fase 5 — Pre-cuenta desde la PWA — COMPLETADA

- [x] `GET /api/orders/{order}/pre-check` con ítems, totales, propinas configuradas y textos de ticket (`ticket_pre_check_header`, `ticket_pre_check_disclaimer`, `ticket_footer_message`)
- [x] Ticket de pre-cuenta ESC/POS (Bluetooth) y por bridge (agente .NET, `type: pre_check`)
- [x] Asignación local "Cuenta (pre-cuenta)" por dispositivo en `/impresoras` (área reservada `0`)
- [x] Botón **Pre-cuenta** en la comanda y registro en la auditoría (`precheck`)
- [x] Tests backend (ítems/totales, 422 sin productos, propinas configuradas) y de encoder

Decisión de QA: **el cobro se realiza en el sistema web**; la PWA solo imprime la pre-cuenta.

## 12. Riesgos y decisiones pendientes

| Riesgo / decisión | Impacto | Mitigación / recomendación |
| :--- | :--- | :--- |
| iOS/Safari no soporta Web Bluetooth | Alto | Bridge local en PC Windows de la sucursal (agente .NET existente) o impresora de red; definir antes de Fase 3 |
| Perfiles GATT variables por marca de impresora | Medio | Descubrimiento automático de característica escribible + validar con 2–3 modelos reales |
| Reconexión BLE requiere gesto del usuario | Medio | Botón "Reconectar impresoras" + reintento guiado |
| Doble impresión (agente + PWA) | Alto | `print_mode=client` evita encolar `PrintJob`; la web mantiene su flujo |
| `is_printed` es global, no por dispositivo | Bajo | Fuente de verdad local en IndexedDB; `is_printed` solo como histórico |
| Ancho/code page incorrectos en acentos | Bajo | Code page 858 + encoder con soporte de páginas |
| Tokens largos sin expiración | Medio | Expiración + abilities + throttle |
| PWA en subruta vs subdominio | Bajo | Recomendado mismo origen `/pwa` |

## 13. Pruebas y verificación

- Backend: `php artisan test` con tests feature nuevos (Sanctum + tenant/sucursal).
- PWA: Vitest + React Testing Library + MSW para API; `FakePrinterTransport` para impresión; Playwright para flujos críticos.
- Matriz manual: Android + impresora BT real; Windows + bridge; dispositivo sin impresora configurada; sin red; reimpresión.

## 14. Criterios de aceptación (Fase 1)

1. Un usuario existente inicia sesión y ve sus sucursales.
2. Puede tomar una comanda completa y enviarla a Laravel.
3. Puede configurar, por área, una impresora distinta en cada dispositivo, y la configuración sobrevive al logout y reinicio.
4. Al enviar, cada área imprime en su impresora local; si falta, la comanda igual se guarda y se avisa.
5. Puede reimprimir una comanda por área o completa.
6. La configuración de impresión de los dispositivos del mismo negocio no se comparte entre ellos.
7. La PWA es instalable y su shell funciona offline.
