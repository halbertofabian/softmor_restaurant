export function FullScreenLoader({ label = 'Cargando…' }: { label?: string }) {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center gap-3 bg-slate-100">
      <div className="h-10 w-10 animate-spin rounded-full border-4 border-primary border-t-transparent" />
      <p className="text-sm text-slate-500">{label}</p>
    </div>
  )
}
