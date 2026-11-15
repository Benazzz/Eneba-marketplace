import { useSearchParams } from 'react-router-dom'
import type { ProductSort } from '../api/types'
import { parseStoreQuery, toSearchParams, type StoreQuery } from '../utils/storeQuery'

/** Adds the value if it's missing, removes it if it's there (a checkbox click). */
function toggle(list: string[], value: string): string[] {
  return list.includes(value) ? list.filter((v) => v !== value) : [...list, value]
}

/**
 * The store's filters, sort and page, read from and written to the URL. The URL is the only copy of
 * this state (no useState), so a shared link restores it and the back button steps through changes:
 * each change is a new history entry.
 *
 * Every change except a page change goes back to page 1, because page 3 of the old results
 * means nothing for the new ones.
 */
export function useStoreQuery() {
  const [searchParams, setSearchParams] = useSearchParams()
  const query = parseStoreQuery(searchParams)

  function update(changes: Partial<StoreQuery>) {
    const next = { ...query, page: 1, ...changes }
    // "Best match" has nothing to rank by once the search term is gone.
    if (!next.q && next.sort === 'relevance') next.sort = null
    setSearchParams(toSearchParams(next))
  }

  return {
    query,
    togglePlatform: (value: string) => update({ platforms: toggle(query.platforms, value) }),
    toggleRegion: (value: string) => update({ regions: toggle(query.regions, value) }),
    setPriceRange: (minPrice: number | null, maxPrice: number | null) => update({ minPrice, maxPrice }),
    setSort: (sort: ProductSort | null) => update({ sort }),
    removeSearch: () => update({ q: '' }),
    /** Clears the search term and every filter. The chosen sort stays. */
    clearAll: () => update({ q: '', platforms: [], regions: [], minPrice: null, maxPrice: null }),
    /** The URL for another page, for pagination links. */
    pageHref: (page: number) => `?${toSearchParams({ ...query, page }).toString()}`,
  }
}
