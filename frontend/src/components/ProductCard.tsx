import { Link } from 'react-router-dom'
// The API type has the same name as this component, so it's imported under another name.
import type { ProductCard as ProductCardData } from '../api/types'
import { ImageWithFallback } from './ImageWithFallback'
import { Price } from './Price'
import styles from './ProductCard.module.css'

type Props = {
  product: ProductCardData
}

// One product tile in a grid (store, and the homepage rows later), like 02-store-filters-top.png:
// cover with a platform strip, name, region, then "From" and the price.
// The whole card is one link to the product page. Buying happens there, because a card has no offer ID.
export function ProductCard({ product }: Props) {
  return (
    <Link to={`/product/${product.slug}`} className={styles.card}>
      <div className={styles.media}>
        <ImageWithFallback
          src={product.coverUrl}
          alt=""
          label={product.name}
          className={styles.cover}
          loading="lazy"
        />
        <span className={styles.platform}>{product.platform}</span>
      </div>

      <div className={styles.body}>
        <h3 className={styles.name} title={product.name}>
          {product.name}
        </h3>
        <p className={styles.region}>{product.region}</p>

        {product.fromPriceCents !== null ? (
          <Price
            prefix="From"
            priceCents={product.fromPriceCents}
            msrpCents={product.msrpCents}
            discountPercent={product.discountPercent}
            className={styles.price}
          />
        ) : (
          <p className={styles.soldOut}>Out of stock</p>
        )}
      </div>
    </Link>
  )
}
