import { Link, useLocation } from 'react-router-dom'
import { CartLine } from '../components/CartLine'
import { CheckoutSteps } from '../components/CheckoutSteps'
import { useCart } from '../hooks/useCart'
import { useCartQuote } from '../hooks/useCartQuote'
import { formatPrice } from '../utils/formatPrice'
import styles from './CheckoutFlow.module.css'

// Step 1 of the purchase flow (08-checkout-cart.png). Open to guests: sign-in is asked for on "Proceed to checkout".
// The cart holds offer IDs and quantities; everything shown here comes from the backend quote.
export function CartPage() {
  const { items, quantityOf, setQuantity, remove } = useCart()
  const { quote, error, isUpdating, removedCount, hasStockProblem, canCheckout, retry } = useCartQuote()
  // A failed checkout (409 out of stock) sends the buyer back here with the reason.
  const notice = (useLocation().state as { notice?: string } | null)?.notice

  // A quote that's still loading may hold items that were just removed: show only what's in the cart now.
  const lines = quote?.lines.filter((line) => quantityOf(line.offer.id) > 0) ?? []
  const keyCount = lines.reduce((sum, line) => sum + quantityOf(line.offer.id), 0)

  return (
    <div className={styles.page}>
      <CheckoutSteps current={1} />

      {notice && (
        <p className={styles.notice} role="alert">
          {notice}
        </p>
      )}
      {removedCount > 0 && (
        <p className={styles.notice} role="status">
          {removedCount === 1 ? 'An item is' : `${removedCount} items are`} no longer sold and{' '}
          {removedCount === 1 ? 'was' : 'were'} removed from your cart.
        </p>
      )}

      {items.length === 0 ? (
        <section className={styles.panel}>
          <h1 className={styles.panelTitle}>My cart</h1>
          <div className={styles.empty}>
            <p>Your cart is empty.</p>
            <Link to="/store" className={styles.emptyLink}>
              Browse the store
            </Link>
          </div>
        </section>
      ) : (
        <div className={styles.layout}>
          <section className={styles.panel} aria-busy={isUpdating}>
            <h1 className={styles.panelTitle}>My cart</h1>

            {error ? (
              <div className={styles.errorBox} role="alert">
                <p>{error.message}</p>
                <button type="button" className={styles.secondaryButton} onClick={retry}>
                  Try again
                </button>
              </div>
            ) : quote === null ? (
              <div className={styles.lines} aria-label="Loading your cart">
                {items.map((item) => (
                  <div key={item.offerId} className={styles.skeletonLine} />
                ))}
              </div>
            ) : (
              <ul className={styles.lines}>
                {lines.map((line) => (
                  <CartLine
                    key={line.offer.id}
                    line={line}
                    quantity={quantityOf(line.offer.id)}
                    // Capped at the stock: + stops there, and − on an "Only N left" line drops straight to N.
                    onQuantityChange={(quantity) => setQuantity(line.offer.id, quantity, line.available)}
                    onRemove={() => remove(line.offer.id)}
                  />
                ))}
              </ul>
            )}
          </section>

          <aside className={`${styles.panel} ${styles.summary}`} aria-label="Summary">
            <h2 className={styles.panelTitle}>Summary</h2>

            {canCheckout ? (
              <Link to="/checkout" className={styles.cta}>
                Proceed to checkout
              </Link>
            ) : (
              <>
                <button type="button" className={styles.cta} disabled>
                  Proceed to checkout
                </button>
                <p className={styles.ctaHint}>
                  {isUpdating
                    ? 'Checking prices and stock…'
                    : hasStockProblem
                      ? 'Some items are sold out or have fewer keys left. Fix the highlighted items to continue.'
                      : error
                        ? 'Your cart could not be checked.'
                        : ''}
                </p>
              </>
            )}

            {quote && (
              <div className={isUpdating ? styles.updating : undefined}>
                <p className={styles.row}>
                  <span>
                    {keyCount} {keyCount === 1 ? 'product' : 'products'}
                  </span>
                  <strong>{formatPrice(quote.totalCents)}</strong>
                </p>
                <p className={styles.total}>
                  <span>Total:</span>
                  <span className={styles.totalAmount}>{formatPrice(quote.totalCents)}</span>
                </p>
              </div>
            )}
          </aside>
        </div>
      )}
    </div>
  )
}
