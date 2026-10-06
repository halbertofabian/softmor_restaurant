export function FullScreenLoader({ label = 'Cargando…' }: { label?: string }) {
  return (
    <div className="app-min-60-screen flex flex-col items-center justify-center gap-3">
      <div className="h-10 w-10 animate-spin rounded-full border-4 border-primary border-t-transparent" />
      <p className="text-sm text-gray-500">{label}</p>
    </div>
  )
}
