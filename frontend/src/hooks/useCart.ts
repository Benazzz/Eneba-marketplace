import { useSyncExternalStore } from 'react'

// The cart lives in localStorage as [{ offerId, quantity }] only (PLAN.md §7). Prices and stock always
// come from the backend, so the browser is never trusted for them.
//
// The store is plain module code outside React. useSyncExternalStore is React's hook for reading
// such a store: every component that calls useCart() sees the same cart and re-renders when it
// changes, without a Context Provider. Think of it as a singleton service that raises a "changed" event.

export type CartItem = {
  offerId: number
  quantity: number
}

const STORAGE_KEY = 'cart'
const EMPTY: CartItem[] = []

/** Raw JSON of the last read, and what it parsed to. */
let cachedRaw: string | null = null
let cachedItems: CartItem[] = EMPTY
const listeners = new Set<() => void>()

function readRaw(): string | null {
  try {
    return localStorage.getItem(STORAGE_KEY)
  } catch {
    return null // storage blocked (e.g. some private modes): behave like an empty cart
  }
}

function isCartItem(value: unknown): value is CartItem {
  const item = value as CartItem
  return (
    typeof item === 'object' &&
    item !== null &&
    Number.isInteger(item.offerId) &&
    Number.isInteger(item.quantity) &&
    item.quantity > 0
  )
}

function parse(raw: string | null): CartItem[] {
  if (raw === null) return EMPTY
  try {
    const data: unknown = JSON.parse(raw)
    // Anything else in storage (hand-edited, an old format) is dropped instead of crashing the page.
    return Array.isArray(data) ? data.filter(isCartItem) : EMPTY
  } catch {
    return EMPTY
  }
}

/**
 * The snapshot React reads. It must return the same array object while nothing changed, or React
 * would re-render forever, so the parsed array is cached against the raw string.
 */
function getSnapshot(): CartItem[] {
  const raw = readRaw()
  if (raw !== cachedRaw) {
    cachedRaw = raw
    cachedItems = parse(raw)
  }
  return cachedItems
}

function write(items: CartItem[]) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(items))
  } catch {
    // Storage full or blocked: the cart just doesn't persist. Nothing useful to show the user here.
  }
  listeners.forEach((notify) => notify())
}

function subscribe(onChange: () => void) {
  listeners.add(onChange)
  // The 'storage' event fires when another tab changes localStorage, so open tabs stay in sync.
  const onStorage = (e: StorageEvent) => {
    if (e.key === STORAGE_KEY || e.key === null) onChange()
  }
  window.addEventListener('storage', onStorage)
  return () => {
    listeners.delete(onChange)
    window.removeEventListener('storage', onStorage)
  }
}

/**
 * Takes these offers out of the cart. A plain function, not part of the hook, so non-component code
 * (useCartQuote's request callback) can call it without depending on a component's render.
 */
export function removeOffersFromCart(offerIds: number[]) {
  const ids = new Set(offerIds)
  write(getSnapshot().filter((item) => !ids.has(item.offerId)))
}

export function useCart() {
  const items = useSyncExternalStore(subscribe, getSnapshot)

  /** How many of this offer are in the cart (0 if none). */
  function quantityOf(offerId: number): number {
    return items.find((item) => item.offerId === offerId)?.quantity ?? 0
  }

  /** Sets an offer's quantity, capped at maxStock. 0 or less removes it. */
  function setQuantity(offerId: number, quantity: number, maxStock = Infinity) {
    // Read the store, not `items` from this render: two quick clicks must both count.
    const current = getSnapshot()
    const capped = Math.min(Math.floor(quantity), maxStock)

    if (capped <= 0) {
      write(current.filter((item) => item.offerId !== offerId))
    } else if (current.some((item) => item.offerId === offerId)) {
      // Keep the item where it was in the list.
      write(current.map((item) => (item.offerId === offerId ? { offerId, quantity: capped } : item)))
    } else {
      write([...current, { offerId, quantity: capped }])
    }
  }

  /** Adds one key of an offer. Returns false when the cart already holds all of its stock. */
  function add(offerId: number, maxStock: number): boolean {
    const current = getSnapshot().find((item) => item.offerId === offerId)?.quantity ?? 0
    if (current >= maxStock) return false
    setQuantity(offerId, current + 1, maxStock)
    return true
  }

  return {
    items,
    /** Total number of keys, for the header badge. */
    count: items.reduce((sum, item) => sum + item.quantity, 0),
    quantityOf,
    add,
    setQuantity,
    remove: (offerId: number) => setQuantity(offerId, 0),
    clear: () => write([]),
  }
}
