import { Suspense, lazy, type ComponentType } from 'react'
import { FullScreenLoader } from '../components/FullScreenLoader'

export function lazyPage(factory: () => Promise<{ default: ComponentType }>) {
  const Component = lazy(factory)

  return (
    <Suspense fallback={<FullScreenLoader label="Cargando…" />}>
      <Component />
    </Suspense>
  )
}
