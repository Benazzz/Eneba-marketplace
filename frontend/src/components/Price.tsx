import { formatPrice } from '../utils/formatPrice'
import styles from './Price.module.css'

type Props = {
  priceCents: number
  msrpCents: number
  /** From the API (0–100). The old price and the badge are only shown when it's above 0. */
  discountPercent: number | null
  size?: 'md' | 'lg'
  className?: string
}

// Price block as on eneba.com: crossed-out list price and a green "-45%" on top, the price below.
export function Price({ priceCents, msrpCents, discountPercent, size = 'md', className }: Props) {
  const hasDiscount = discountPercent !== null && discountPercent > 0

  return (
    <div className={`${styles.price} ${size === 'lg' ? styles.large : ''} ${className ?? ''}`}>
      {hasDiscount && (
        <p className={styles.was}>
          <s aria-label={`Was ${formatPrice(msrpCents)}`}>{formatPrice(msrpCents)}</s>
          <span className={styles.discount}>-{discountPercent}%</span>
        </p>
      )}
      <p className={styles.now}>{formatPrice(priceCents)}</p>
    </div>
  )
}
