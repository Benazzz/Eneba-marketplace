import { useId, useRef } from 'react'
import type { ProductCard as ProductCardData } from '../api/types'
import { ChevronDownIcon } from '../components/icons'
import { ProductCard } from '../components/ProductCard'
import styles from './ProductRow.module.css'

type Props = {
  title: string
  products: ProductCardData[]
}

// A titled row of product cards that scrolls sideways (best sellers, deals, new releases), as in 01-home.png.
// It uses the store's ProductCard. Five cards fit on desktop; the arrows move one screenful at a time.
// On phones the arrows are hidden and the row is swiped.
export function ProductRow({ title, products }: Props) {
  const titleId = useId()
  // A ref is like holding a reference to the rendered DOM element, so the arrow buttons can scroll it.
  const trackRef = useRef<HTMLUListElement>(null)

  function scrollByPage(direction: -1 | 1) {
    const track = trackRef.current
    if (track) track.scrollBy({ left: direction * track.clientWidth, behavior: 'smooth' })
  }

  return (
    <section className={styles.row} aria-labelledby={titleId}>
      <div className={styles.header}>
        <h2 id={titleId} className={styles.title}>
          {title}
        </h2>
        <div className={styles.arrows}>
          <button type="button" className={styles.arrow} aria-label={`Scroll ${title} back`} onClick={() => scrollByPage(-1)}>
            <ChevronDownIcon size={20} className={styles.prev} />
          </button>
          <button type="button" className={styles.arrow} aria-label={`Scroll ${title} forward`} onClick={() => scrollByPage(1)}>
            <ChevronDownIcon size={20} className={styles.next} />
          </button>
        </div>
      </div>

      <ul ref={trackRef} className={styles.track}>
        {products.map((product) => (
          <li key={product.id} className={styles.item}>
            <ProductCard product={product} />
          </li>
        ))}
      </ul>
    </section>
  )
}

/** The row's shape (title bar and five cards) while its products load. */
export function ProductRowSkeleton() {
  return (
    <div className={styles.row} aria-hidden="true">
      <div className={styles.header}>
        <div className={`${styles.skeleton} ${styles.skeletonTitle}`} />
      </div>
      <div className={styles.track}>
        {Array.from({ length: 5 }, (_, i) => (
          <div key={i} className={`${styles.item} ${styles.skeleton} ${styles.skeletonCard}`} />
        ))}
      </div>
    </div>
  )
}
