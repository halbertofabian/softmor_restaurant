import { useMemo, useState } from 'react'
import { CloseIcon, PlusIcon, SearchIcon } from '../../components/ui/icons'
import type { Product, ProductCategory } from '../../lib/api/types'
import { formatMoney } from '../../lib/format'

interface ProductCatalogProps {
  categories: ProductCategory[]
  onSelect: (product: Product) => void
}

function CategoryChip({
  active,
  onClick,
  children,
}: {
  active: boolean
  onClick: () => void
  children: string
}) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={`shrink-0 whitespace-nowrap rounded-full border px-3.5 py-1.5 text-xs font-medium transition ${
        active
          ? 'border-primary bg-primary/15 text-primary'
          : 'border-white/10 text-gray-400 hover:text-white'
      }`}
    >
      {children}
    </button>
  )
}

function ProductCard({ product, onSelect }: { product: Product; onSelect: (product: Product) => void }) {
  const soldOut = product.controls_inventory && product.stock <= 0
  const initial = product.name.trim().charAt(0).toUpperCase()

  return (
    <button
      type="button"
      disabled={soldOut}
      onClick={() => onSelect(product)}
      className="relative flex flex-col items-center rounded-2xl border border-primary/15 bg-gradient-to-br from-card to-[#27272a] p-3 text-center shadow-xl shadow-black/20 transition hover:border-primary/50 disabled:cursor-not-allowed disabled:opacity-40"
    >
      <span className="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-primary-light to-primary text-base font-bold text-black">
        {initial}
      </span>

      <span className="mt-2 line-clamp-2 min-h-8 text-xs font-semibold text-white">
        {product.name}
      </span>
      <span className="mt-1 text-sm font-bold text-primary">{formatMoney(product.price)}</span>

      {product.type === 'combo' && <span className="text-[10px] text-gray-500">Combo</span>}
      {product.type !== 'combo' && product.flavors.length > 0 && (
        <span className="text-[10px] text-gray-500">
          {product.flavors.length} sabor{product.flavors.length === 1 ? '' : 'es'}
        </span>
      )}
      {soldOut && <span className="text-[10px] font-medium text-red-400">Agotado</span>}

      <span className="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-primary/15 text-primary">
        <PlusIcon className="h-3.5 w-3.5" />
      </span>
    </button>
  )
}

export function ProductCatalog({ categories, onSelect }: ProductCatalogProps) {
  const [query, setQuery] = useState('')
  const [activeId, setActiveId] = useState<number | 'all'>('all')

  const allProducts = useMemo(
    () => categories.flatMap((category) => category.products),
    [categories],
  )

  const activeCategory =
    activeId === 'all' ? null : (categories.find((category) => category.id === activeId) ?? null)

  const normalizedQuery = query.trim().toLowerCase()
  const isSearching = normalizedQuery.length > 0

  const products = isSearching
    ? allProducts.filter((product) => product.name.toLowerCase().includes(normalizedQuery))
    : (activeCategory?.products ?? allProducts)

  return (
    <div className="space-y-3">
      <div className="relative">
        <SearchIcon className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-primary" />
        <input
          type="search"
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          placeholder="Buscar productos..."
          className="w-full rounded-2xl border border-white/10 bg-card py-3 pl-10 pr-10 text-sm text-white outline-none transition placeholder:text-gray-600 focus:border-primary"
        />
        {query && (
          <button
            type="button"
            onClick={() => setQuery('')}
            className="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-1 text-gray-500 transition hover:text-white"
            aria-label="Limpiar búsqueda"
          >
            <CloseIcon className="h-4 w-4" />
          </button>
        )}
      </div>

      {!isSearching && (
        <div className="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1">
          <CategoryChip active={activeId === 'all'} onClick={() => setActiveId('all')}>
            Todo
          </CategoryChip>
          {categories.map((category) => (
            <CategoryChip
              key={category.id}
              active={activeId === category.id}
              onClick={() => setActiveId(category.id)}
            >
              {category.name}
            </CategoryChip>
          ))}
        </div>
      )}

      {isSearching && (
        <p className="px-1 text-xs text-gray-500">
          {products.length} resultado{products.length === 1 ? '' : 's'} para “{query.trim()}”
        </p>
      )}

      {products.length === 0 ? (
        <div className="rounded-3xl border border-white/5 bg-card p-6 text-center shadow-xl shadow-black/20">
          <p className="text-sm text-gray-400">
            {isSearching
              ? 'No se encontraron productos con ese nombre.'
              : 'No hay productos disponibles en esta categoría.'}
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-2 gap-3 @xl:grid-cols-3 @4xl:grid-cols-4">
          {products.map((product) => (
            <ProductCard key={product.id} product={product} onSelect={onSelect} />
          ))}
        </div>
      )}
    </div>
  )
}
