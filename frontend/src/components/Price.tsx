import { formatPrice } from '../utils/formatPrice'
import styles from './Price.module.css'

type Props = {
  priceCents: number
  msrpCents: number
  /** From the API (0–100). The old price and the badge are only shown when it's above 0. */
  discountPercent: number | null
  size?: 'md' | 'lg'
  /** Text before the old price on the top line, e.g. "From" on product cards. Shown even without a discount. */
  prefix?: string
  className?: string
}

// Price block as on eneba.com: crossed-out list price and a green "-45%" on top, the price below.
export function Price({ priceCents, msrpCents, discountPercent, size = 'md', prefix, className }: Props) {
  const hasDiscount = discountPercent !== null && discountPercent > 0

  return (
    <div className={`${styles.price} ${size === 'lg' ? styles.large : ''} ${className ?? ''}`}>
      {(hasDiscount || prefix) && (
        <p className={styles.was}>
          {prefix && <span className={styles.prefix}>{prefix}</span>}
          {hasDiscount && (
            <>
              <s aria-label={`Was ${formatPrice(msrpCents)}`}>{formatPrice(msrpCents)}</s>
              <span className={styles.discount}>-{discountPercent}%</span>
            </>
          )}
        </p>
      )}
      <p className={styles.now}>{formatPrice(priceCents)}</p>
    </div>
  )
}
