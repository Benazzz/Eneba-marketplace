import { Link } from 'react-router-dom'
import type { CartQuoteLine } from '../api/types'
import { formatPrice } from '../utils/formatPrice'
import { ImageWithFallback } from './ImageWithFallback'
import { TrashIcon } from './icons'
import styles from './CartLine.module.css'

/** At or below this many keys, the line says how many are left (same as OfferRow). */
const LOW_STOCK = 3

type Props = {
  line: CartQuoteLine
  /** From the cart itself, so +/- show at once, before the new quote arrives. */
  quantity: number
  /** Without these two, the line is read-only (the checkout page's summary). */
  onQuantityChange?: (quantity: number) => void
  onRemove?: () => void
}

// One product in the cart (08-checkout-cart.png): cover, seller, name, quantity stepper and price.
// The unit price always comes from the backend quote; only the multiplication by the quantity happens here.
export function CartLine({ line, quantity, onQuantityChange, onRemove }: Props) {
  const { offer, product, available } = line
  const editable = onQuantityChange !== undefined && onRemove !== undefined
  const soldOut = available === 0
  const tooMany = !soldOut && quantity > available

  return (
    <li className={`${styles.line} ${editable ? '' : styles.compact} ${soldOut || tooMany ? styles.problem : ''}`}>
      <Link to={`/product/${product.slug}`} className={styles.coverLink} tabIndex={-1} aria-hidden="true">
        <ImageWithFallback src={product.coverUrl} alt="" label={product.name} className={styles.cover} loading="lazy" />
      </Link>

      <div className={styles.info}>
        <p className={styles.seller}>
          Sold by <strong>{offer.seller.name}</strong>
        </p>
        <Link to={`/product/${product.slug}`} className={styles.name}>
          {product.name}
        </Link>
        <p className={styles.meta}>Digital product · {product.platform}</p>

        {soldOut && <p className={styles.error}>Sold out. Please remove it to continue.</p>}
        {tooMany && (
          <p className={styles.error}>
            Only {available} left. Lower the quantity to continue.
          </p>
        )}
        {!soldOut && !tooMany && available <= LOW_STOCK && <p className={styles.lowStock}>Only {available} left</p>}
      </div>

      <div className={styles.side}>
        {editable && (
          <button type="button" className={styles.remove} onClick={onRemove} aria-label={`Remove ${product.name}`}>
            <TrashIcon size={20} />
          </button>
        )}

        <div className={styles.priceRow}>
          {editable && !soldOut ? (
            <div className={styles.stepper}>
              <button
                type="button"
                onClick={() => onQuantityChange(quantity - 1)}
                disabled={quantity <= 1}
                aria-label={`One less ${product.name}`}
              >
                −
              </button>
              <span aria-live="polite" aria-label={`Quantity ${quantity}`}>
                {quantity}
              </span>
              <button
                type="button"
                onClick={() => onQuantityChange(quantity + 1)}
                disabled={quantity >= available}
                aria-label={`One more ${product.name}`}
                title={quantity >= available ? 'No more keys in stock' : undefined}
              >
                +
              </button>
            </div>
          ) : (
            <span className={styles.quantity}>× {quantity}</span>
          )}
          <span className={styles.price}>{formatPrice(offer.priceCents * quantity)}</span>
        </div>
      </div>
    </li>
  )
}
