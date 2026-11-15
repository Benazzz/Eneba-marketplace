import type { Offer } from '../api/types'
import { AddToCartButton, BuyNowButton, CartLimitHint } from './CartButtons'
import { StarIcon, VerifiedIcon } from './icons'
import { Price } from './Price'
import styles from './OfferRow.module.css'

/** At or below this many keys, the row warns that stock is low. */
const LOW_STOCK = 3

type Props = {
  offer: Offer
  msrpCents: number
}

// One seller's offer in the product page's comparison list (05-product-offers.png):
// seller on the left, price and buttons on the right.
export function OfferRow({ offer, msrpCents }: Props) {
  const { seller } = offer

  return (
    <li className={styles.row}>
      {/* No seller logos in the data: a letter avatar stands in for them. */}
      <span className={styles.avatar} aria-hidden="true">
        {seller.name.charAt(0).toUpperCase()}
      </span>

      <div className={styles.seller}>
        <p className={styles.name}>
          {seller.name}
          {seller.verified && (
            <span className={styles.verified} title="Verified seller">
              <VerifiedIcon size={16} />
              <span className="sr-only">(verified seller)</span>
            </span>
          )}
        </p>
        <p className={styles.rating}>
          <StarIcon size={14} className={styles.star} />
          <span>
            <strong>{seller.rating.toFixed(2)}</strong> rating
          </span>
        </p>
      </div>

      <div className={styles.buy}>
        <div className={styles.priceInfo}>
          <Price
            priceCents={offer.priceCents}
            msrpCents={msrpCents}
            discountPercent={offer.discountPercent}
            className={styles.price}
          />
          {offer.stock <= LOW_STOCK && <p className={styles.lowStock}>Only {offer.stock} left</p>}
        </div>
        <div className={styles.actions}>
          <div className={styles.buttons}>
            <BuyNowButton offer={offer} variant="outline" label={seller.name} />
            <AddToCartButton offer={offer} variant="outline" label={seller.name} />
          </div>
          <CartLimitHint offer={offer} />
        </div>
      </div>
    </li>
  )
}
