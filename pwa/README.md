# PWA GestionalFood

PWA en React + TypeScript + Vite que consume la API de Laravel de GestionalFood.

Estado: **Fase 1 (base)** — login, selección de sucursal, permisos y shell instalable. El mapa de mesas y la impresión local llegan en las siguientes fases. Ver `../docs/pwa_roadmap.md`.

## Stack

- React 19 + Vite + TypeScript
- React Router v7 (SPA con `basename` `/pwa/`)
- TanStack Query (estado de servidor) + Zustand (sesión persistida)
- react-hook-form + Zod
- Tailwind CSS 4
- vite-plugin-pwa (Workbox) + `@vite-pwa/assets-generator`

## Requisitos

- Node 20+
- Laravel corriendo con `iniciar.bat` (`http://127.0.0.1:1010`)

## Desarrollo

```bash
cd pwa
npm install
cp .env.example .env   # opcional: ajustar VITE_PROXY_TARGET
npm run dev
```

Abrir `http://localhost:5173/pwa/`. El proxy de Vite redirige `/api` a Laravel (`VITE_PROXY_TARGET`, por defecto `http://127.0.0.1:1010`).

## Build y despliegue

```bash
npm run build
```

El build sale en `pwa/dist/` y está pensado para servirse en `https://<dominio>/pwa/` (mismo origen que Laravel, `VITE_API_URL=/api`).

## Estructura

```
src/
├─ app/                 router, guards, layout
├─ features/
│  ├─ auth/             login y selección de sucursal
│  └─ tables/           placeholder de mesas (Fase 2)
├─ lib/
│  ├─ api/              cliente fetch, tipos y endpoints
│  └─ device.ts         nombre del dispositivo (token de Sanctum)
├─ stores/              authStore (Zustand persistido)
└─ components/          banner offline, loaders
```

## Privacidad local

La configuración de impresoras (Fase 3) se guardará solo en IndexedDB de este dispositivo. No se sincroniza con Laravel.
