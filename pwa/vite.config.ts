import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig, loadEnv } from 'vite'
import { VitePWA } from 'vite-plugin-pwa'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const proxyTarget = env.VITE_PROXY_TARGET || 'http://127.0.0.1:1010'

  return {
    base: '/pwa/',
    plugins: [
      react(),
      tailwindcss(),
      VitePWA({
        registerType: 'autoUpdate',
        devOptions: {
          enabled: true,
          type: 'module',
        },
        includeAssets: ['favicon.ico', 'apple-touch-icon-180x180.png', 'gestionalfood.png'],
        manifest: {
          name: 'GestionalFood',
          short_name: 'GestionalFood',
          description: 'Toma de comandas e impresión local por área de preparación.',
          lang: 'es',
          start_url: '/pwa/',
          scope: '/pwa/',
          display: 'standalone',
          orientation: 'portrait',
          theme_color: '#E59A1A',
          background_color: '#09090b',
          icons: [
            { src: 'pwa-64x64.png', sizes: '64x64', type: 'image/png' },
            { src: 'pwa-192x192.png', sizes: '192x192', type: 'image/png' },
            { src: 'pwa-512x512.png', sizes: '512x512', type: 'image/png' },
            {
              src: 'maskable-icon-512x512.png',
              sizes: '512x512',
              type: 'image/png',
              purpose: 'maskable',
            },
          ],
        },
        workbox: {
          cleanupOutdatedCaches: true,
          navigateFallback: 'index.html',
          globPatterns: ['**/*.{js,css,html,svg,png,ico,woff2}'],
        },
      }),
    ],
    server: {
      host: true,
      allowedHosts: true,
      port: 5173,
      proxy: {
        '/api': {
          target: proxyTarget,
          changeOrigin: true,
        },
      },
    },
  }
})
