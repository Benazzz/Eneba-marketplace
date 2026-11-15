import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import type { ProductFilters, ProductListResponse, ProductSort } from '../api/types'
import { CloseIcon, FilterIcon, HomeIcon } from '../components/icons'
import { PageTitle } from '../components/PageTitle'
import { Pagination } from '../components/Pagination'
import { ProductCard } from '../components/ProductCard'
import { StatusMessage } from '../components/StatusMessage'
import { StoreFilters } from '../components/StoreFilters'
import { useFetch } from '../hooks/useFetch'
import { useStoreQuery } from '../hooks/useStoreQuery'
import { formatPrice } from '../utils/formatPrice'
import { activeFilterCount, defaultSort, PAGE_SIZE, SORT_OPTIONS, toApiQuery } from '../utils/storeQuery'
import styles from './StorePage.module.css'

/** "/api/products" + "q=x" → "/api/products?q=x", without a dangling "?" when there's no query. */
function withQuery(path: string, queryString: string): string {
  return queryString ? `${path}?${queryString}` : path
}

// The catalog (PLAN.md §1 "Store"), as in 02/03-store-filters-*.png: filter panel on the left, then chips,
// result count and sort, the card grid and page links. Everything the user picks lives in the URL
// (useStoreQuery), so links like /store?sort=discount or /store?q=witcher open the right view.
export function StorePage() {
  const { query, togglePlatform, toggleRegion, setPriceRange, setSort, removeSearch, clearAll, pageHref } =
    useStoreQuery()
  // Mobile only: the filter panel is folded away behind a button (CSS shows it always on desktop).
  const [isFiltersOpen, setIsFiltersOpen] = useState(false)

  // Two independent requests: the grid, and the filter options with their counts. keepPrevious keeps the
  // old grid on screen (dimmed) while the next one loads, instead of flashing an empty page.
  const list = useFetch<ProductListResponse>(withQuery('/api/products', toApiQuery(query)), { keepPrevious: true })
  const facets = useFetch<ProductFilters>(
    withQuery('/api/products/filters', toApiQuery(query, { forFilters: true })),
    { keepPrevious: true },
  )

  // A new page starts at the top, like a normal page load. Also runs for the back button.
  useEffect(() => {
    window.scrollTo({ top: 0 })
  }, [query.page])

  const filterCount = activeFilterCount(query)
  const hasAnyFilter = filterCount > 0 || query.q !== ''
  const currentSort = query.sort ?? defaultSort(query.q)
  // "Best match" only makes sense while searching.
  const sortOptions = SORT_OPTIONS.filter((o) => o.value !== 'relevance' || query.q)

  function handleSortChange(value: ProductSort) {
    // The default sort isn't written to the URL, so links stay short.
    setSort(value === defaultSort(query.q) ? null : value)
  }

  return (
    <div className={styles.page}>
      <PageTitle>{query.q ? `Results for "${query.q}"` : 'Store'}</PageTitle>
      <nav aria-label="Breadcrumb" className={styles.breadcrumb}>
        <Link to="/" aria-label="Home">
          <HomeIcon size={18} />
        </Link>
        <span aria-hidden="true">›</span>
        <span aria-current="page">Store</span>
      </nav>

      <h1 className={styles.title}>
        {query.q ? `Results for "${query.q}"` : 'Buy the best video game keys at the best price!'}
      </h1>

      {/* Mobile only: opens the panel right below it. */}
      <button
        type="button"
        className={styles.filtersToggle}
        aria-expanded={isFiltersOpen}
        aria-controls="store-filters"
        onClick={() => setIsFiltersOpen((open) => !open)}
      >
        <FilterIcon size={18} />
        Filters{filterCount > 0 && ` (${filterCount})`}
      </button>

      <div className={styles.layout}>
        <aside
          id="store-filters"
          className={`${styles.sidebar} ${isFiltersOpen ? styles.sidebarOpen : ''}`}
          aria-label="Filters"
        >
          <StoreFilters
            query={query}
            filters={facets.data}
            onTogglePlatform={togglePlatform}
            onToggleRegion={toggleRegion}
            onPriceChange={setPriceRange}
          />
        </aside>

        <section className={styles.results} aria-label="Products">
          <div className={styles.chips}>
            {query.q && <Chip label={`Search: "${query.q}"`} onRemove={removeSearch} />}
            {query.platforms.map((p) => (
              <Chip key={`p-${p}`} label={p} onRemove={() => togglePlatform(p)} />
            ))}
            {query.regions.map((r) => (
              <Chip key={`r-${r}`} label={r.toLowerCase()} capitalize onRemove={() => toggleRegion(r)} />
            ))}
            {(query.minPrice !== null || query.maxPrice !== null) && (
              <Chip label={priceLabel(query.minPrice, query.maxPrice)} onRemove={() => setPriceRange(null, null)} />
            )}
            {hasAnyFilter && (
              <button type="button" className={styles.clearAll} onClick={clearAll}>
                Clear all
              </button>
            )}
          </div>

          <div className={styles.toolbar}>
            {/* aria-live: screen readers hear the new count after each filter change. */}
            <p className={styles.count} aria-live="polite">
              {list.data && (
                <>
                  Results found: <strong>{list.data.total}</strong>
                </>
              )}
            </p>
            <label className={styles.sort}>
              <span>Sort by:</span>
              <select
                className={styles.sortSelect}
                value={currentSort}
                onChange={(e) => handleSortChange(e.target.value as ProductSort)}
              >
                {sortOptions.map((o) => (
                  <option key={o.value} value={o.value}>
                    {o.label}
                  </option>
                ))}
              </select>
            </label>
          </div>

          <StoreResults
            data={list.data}
            errorMessage={list.error?.message ?? null}
            isLoading={list.isLoading}
            hasAnyFilter={hasAnyFilter}
            onClearAll={clearAll}
            pageHref={pageHref}
          />
        </section>
      </div>
    </div>
  )
}

/** "€10.00 – €30.00", "From €10.00" or "Up to €30.00" (inputs are euros, formatPrice takes cents). */
function priceLabel(min: number | null, max: number | null): string {
  const eur = (value: number) => formatPrice(Math.round(value * 100))
  if (min !== null && max !== null) return `${eur(min)} – ${eur(max)}`
  return min !== null ? `From ${eur(min)}` : `Up to ${eur(max ?? 0)}`
}

/** An active filter with a × to remove it. */
function Chip({ label, onRemove, capitalize = false }: { label: string; onRemove: () => void; capitalize?: boolean }) {
  return (
    <button
      type="button"
      className={`${styles.chip} ${capitalize ? styles.capitalize : ''}`}
      onClick={onRemove}
      aria-label={`Remove filter: ${label}`}
    >
      {label}
      <CloseIcon size={16} />
    </button>
  )
}

type StoreResultsProps = {
  data: ProductListResponse | null
  errorMessage: string | null
  isLoading: boolean
  hasAnyFilter: boolean
  onClearAll: () => void
  pageHref: (page: number) => string
}

/** The grid area: skeleton, error, empty state, or the cards with page links. */
function StoreResults({ data, errorMessage, isLoading, hasAnyFilter, onClearAll, pageHref }: StoreResultsProps) {
  if (!data) {
    if (errorMessage) {
      return (
        <StatusMessage title="Something went wrong" headingLevel="h2" isError>
          {errorMessage}
        </StatusMessage>
      )
    }
    // First load: placeholder cards in the grid's shape.
    return (
      <ul className={styles.grid} aria-hidden="true">
        {Array.from({ length: 8 }, (_, i) => (
          <li key={i} className={`${styles.skeletonCard} skeleton`} />
        ))}
      </ul>
    )
  }

  if (data.total === 0) {
    return (
      <StatusMessage
        title="No results found"
        headingLevel="h2"
        action={hasAnyFilter ? { label: 'Clear all filters', onClick: onClearAll } : undefined}
      >
        {hasAnyFilter ? 'Try another spelling, or remove some filters.' : 'There are no products in the store yet.'}
      </StatusMessage>
    )
  }

  // An old or hand-edited link can point past the last page.
  if (data.items.length === 0) {
    return (
      <StatusMessage
        title="This page is empty"
        headingLevel="h2"
        action={{ label: 'Go to the first page', to: pageHref(1) }}
      >
        There {data.pages === 1 ? 'is only 1 page' : `are only ${data.pages} pages`} of results.
      </StatusMessage>
    )
  }

  return (
    <>
      {/* aria-busy tells screen readers the list is about to change. */}
      <ul className={`${styles.grid} ${isLoading ? styles.gridLoading : ''}`} aria-busy={isLoading}>
        {data.items.map((product) => (
          <li key={product.id}>
            <ProductCard product={product} />
          </li>
        ))}
      </ul>
      <Pagination page={data.page} pages={data.pages} hrefFor={pageHref} />
      {data.pages > 1 && (
        <p className={styles.pageInfo}>
          Showing {(data.page - 1) * PAGE_SIZE + 1}–{(data.page - 1) * PAGE_SIZE + data.items.length} of {data.total}
        </p>
      )}
    </>
  )
}
