import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { useEffect } from 'react'
import { RouterProvider } from 'react-router-dom'
import { OrientationGuard } from './components/OrientationGuard'
import { Toaster } from './components/ui/Toaster'
import { router } from './app/router'
import { processAgentPairingFromUrl } from './lib/printing/agent'
import { useToastStore } from './stores/toastStore'

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
      staleTime: 0,
      refetchOnMount: 'always',
    },
  },
})

export default function App() {
  useEffect(() => {
    function handlePairing() {
      void processAgentPairingFromUrl().then((link) => {
        if (link) {
          useToastStore
            .getState()
            .push('GestionalFood Printer vinculada. Ya puedes imprimir.', 'success')
        }
      })
    }

    handlePairing()
    window.addEventListener('popstate', handlePairing)
    document.addEventListener('visibilitychange', handlePairing)

    return () => {
      window.removeEventListener('popstate', handlePairing)
      document.removeEventListener('visibilitychange', handlePairing)
    }
  }, [])

  return (
    <QueryClientProvider client={queryClient}>
      <RouterProvider router={router} />
      <OrientationGuard />
      <Toaster />
    </QueryClientProvider>
  )
}
