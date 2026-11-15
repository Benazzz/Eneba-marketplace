import { useEffect, useState } from 'react'
import { ApiError, apiFetch } from '../api/client'
import type { CartQuote } from '../api/types'
import { removeOffersFromCart, useCart, type CartItem } from './useCart'

// Prices the cart through POST /api/cart/quote whenever it changes (PLAN.md §7). The cart in localStorage only holds
// offer IDs and quantities; prices, names and stock always come from this backend answer.

type QuoteResult = {
  /** The cart (as JSON) this answer belongs to, so an answer for an older cart is recognised as stale. */
  key: string
  quote: CartQuote | null
  error: ApiError | null
}

export function useCartQuote() {
  const { items } = useCart()
  // Bumped by retry(), so the same cart can be asked about again after an error.
  const [attempt, setAttempt] = useState(0)
  // The cart array is a new object after every change, but its JSON only changes when the content does.
  // Using it as the effect dependency means "re-quote when the cart's content changes (or on retry)".
  const cartJson = JSON.stringify(items)
  const key = `${attempt}:${cartJson}`
  const [result, setResult] = useState<QuoteResult | null>(null)
  // How many cart items were dropped because their offer no longer exists, for a notice on the page.
  const [removedCount, setRemovedCount] = useState(0)

  useEffect(() => {
    const cart = JSON.parse(cartJson) as CartItem[]
    if (cart.length === 0) return

    // Cancels an older request when the cart changes again (e.g. quick +/- clicks) or the page closes.
    const controller = new AbortController()
    apiFetch<CartQuote>('/api/cart/quote', { method: 'POST', body: { items: cart }, signal: controller.signal })
      .then((quote) => {
        if (controller.signal.aborted) return
        // Offers the API doesn't know (removed, or a hand-edited cart) can never be bought: take them out of the
        // cart. That changes the cart, so a fresh quote follows by itself.
        const known = new Set(quote.lines.map((line) => line.offer.id))
        const unknown = cart.filter((item) => !known.has(item.offerId)).map((item) => item.offerId)
        if (unknown.length > 0) {
          removeOffersFromCart(unknown)
          setRemovedCount((count) => count + unknown.length)
        }
        setResult({ key, quote, error: null })
      })
      .catch((err: unknown) => {
        if (controller.signal.aborted) return
        const error = err instanceof ApiError ? err : new ApiError(0, 'unknown_error', String(err))
        setResult({ key, quote: null, error })
      })
    return () => controller.abort()
  }, [key, cartJson])

  const isEmpty = items.length === 0
  const isCurrent = result?.key === key
  // While a new answer loads, the previous quote stays on screen (marked as updating) instead of flashing empty.
  const quote = isEmpty ? null : (result?.quote ?? null)
  const currentQuote = isCurrent ? quote : null

  // Only judged on an up-to-date answer, never on a stale one.
  const quotedIds = new Set(currentQuote?.lines.map((line) => line.offer.id))
  const missingOfferIds = currentQuote ? items.filter((item) => !quotedIds.has(item.offerId)).map((i) => i.offerId) : []
  const hasStockProblem = currentQuote?.lines.some((line) => line.quantity > line.available) ?? false

  return {
    quote,
    error: isCurrent && !isEmpty ? result.error : null,
    /** No answer yet for the cart as it is now. */
    isUpdating: !isEmpty && !isCurrent,
    /** Cart items dropped so far because their offer no longer exists. */
    removedCount,
    /** Some line is sold out or asks for more than is left. */
    hasStockProblem,
    /** True only when the current cart is priced and every line can be bought. */
    canCheckout: currentQuote !== null && currentQuote.lines.length > 0 && missingOfferIds.length === 0 && !hasStockProblem,
    /** Asks again, e.g. after a network error. */
    retry: () => setAttempt((n) => n + 1),
  }
}
