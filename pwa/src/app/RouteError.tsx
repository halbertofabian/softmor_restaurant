import { Link, useRouteError } from 'react-router-dom'
import { Card } from '../components/ui/Card'

export function RouteError() {
  const error = useRouteError()
  const message = error instanceof Error ? error.message : 'Ocurrió un error inesperado.'

  return (
    <div className="bg-grid flex min-h-screen items-center justify-center bg-ink px-4 py-10">
      <div className="w-full max-w-md">
        <Card>
          <h1 className="text-lg font-bold text-white">Algo salió mal</h1>
          <p className="mt-2 break-words text-sm text-gray-400">{message}</p>

          <div className="mt-4 flex flex-wrap gap-2">
            <Link
              to="/"
              className="rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-black shadow-lg shadow-primary/20 transition hover:bg-primary-dark"
            >
              Volver al inicio
            </Link>
            <button
              type="button"
              onClick={() => window.location.reload()}
              className="rounded-xl border border-white/10 px-4 py-2.5 text-sm font-medium text-gray-300 transition hover:bg-white/5"
            >
              Recargar
            </button>
          </div>
        </Card>
      </div>
    </div>
  )
}
