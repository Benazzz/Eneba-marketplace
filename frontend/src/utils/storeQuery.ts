// The store's state lives in the browser URL (PLAN.md §5 / Decision 30), e.g.
//   /store?q=witcher&platform=Steam&platform=PSN&region=GLOBAL&minPrice=10&maxPrice=30&sort=price_asc&page=2
// The browser URL uses euros and plain repeated keys, so a shared link stays readable.
// The API wants cents and PHP's list syntax (platform[]=…), so toApiQuery() translates.

import type { ProductSort } from '../api/types'

export type StoreQuery = {
  q: string
  platforms: string[]
  regions: string[]
  /** Euros, as typed by the user. */
  minPrice: number | null
  maxPrice: number | null
  /** Null = the API's default (best match while searching, most popular otherwise). */
  sort: ProductSort | null
  page: number
}

/** UI labels for the API's sort values. `relevance` is only offered while there's a search term. */
export const SORT_OPTIONS: { value: ProductSort; label: string }[] = [
  { value: 'relevance', label: 'Best match' },
  { value: 'popular', label: 'Most popular' },
  { value: 'price_asc', label: 'Price: low to high' },
  { value: 'price_desc', label: 'Price: high to low' },
  { value: 'discount', label: 'Biggest discount' },
  { value: 'newest', label: 'Newest' },
]

/** The sort the API applies when none is chosen. */
export function defaultSort(q: string): ProductSort {
  return q.trim() ? 'relevance' : 'popular'
}

/** Items per page. Matches the API's default, so it isn't sent. */
export const PAGE_SIZE = 24

function parsePrice(value: string | null): number | null {
  if (value === null || value.trim() === '') return null
  const n = Number(value)
  return Number.isFinite(n) && n >= 0 ? n : null
}

/**
 * Reads the browser URL. Anything unknown or invalid is ignored rather than sent on,
 * so a hand-edited link shows the store instead of an error.
 */
export function parseStoreQuery(params: URLSearchParams): StoreQuery {
  const sort = params.get('sort')
  const page = Number(params.get('page'))

  return {
    q: params.get('q') ?? '',
    platforms: params.getAll('platform'),
    regions: params.getAll('region'),
    minPrice: parsePrice(params.get('minPrice')),
    maxPrice: parsePrice(params.get('maxPrice')),
    sort: SORT_OPTIONS.some((o) => o.value === sort) ? (sort as ProductSort) : null,
    page: Number.isInteger(page) && page >= 1 ? page : 1,
  }
}

/** Writes the browser URL. Defaults (no filter, page 1, default sort) are left out, so links stay short. */
export function toSearchParams(query: StoreQuery): URLSearchParams {
  const params = new URLSearchParams()
  if (query.q) params.set('q', query.q)
  query.platforms.forEach((p) => params.append('platform', p))
  query.regions.forEach((r) => params.append('region', r))
  if (query.minPrice !== null) params.set('minPrice', String(query.minPrice))
  if (query.maxPrice !== null) params.set('maxPrice', String(query.maxPrice))
  if (query.sort) params.set('sort', query.sort)
  if (query.page > 1) params.set('page', String(query.page))
  return params
}

/** Euros → integer cents, without float noise (19.99 * 100 = 1998.9999…). */
function toCents(euros: number): number {
  return Math.round(euros * 100)
}

/**
 * The query string for GET /api/products, or for /api/products/filters with `forFilters`
 * (which only takes the search term and the filters).
 */
export function toApiQuery(query: StoreQuery, { forFilters = false } = {}): string {
  const params = new URLSearchParams()
  if (query.q.trim()) params.set('q', query.q.trim())
  query.platforms.forEach((p) => params.append('platform[]', p))
  query.regions.forEach((r) => params.append('region[]', r))
  if (query.minPrice !== null) params.set('minPrice', String(toCents(query.minPrice)))
  if (query.maxPrice !== null) params.set('maxPrice', String(toCents(query.maxPrice)))
  if (!forFilters) {
    if (query.sort) params.set('sort', query.sort)
    if (query.page > 1) params.set('page', String(query.page))
  }
  return params.toString()
}

/** How many filters are active (for the mobile "Filters (n)" button). The search term isn't counted. */
export function activeFilterCount(query: StoreQuery): number {
  const price = query.minPrice !== null || query.maxPrice !== null ? 1 : 0
  return query.platforms.length + query.regions.length + price
}
