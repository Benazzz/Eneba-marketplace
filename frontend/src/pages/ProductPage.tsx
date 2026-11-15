import { Link, useParams } from 'react-router-dom'
import type { ProductDetail } from '../api/types'
import { AddToCartButton, BuyNowButton, CartLimitHint } from '../components/CartButtons'
import { HomeIcon } from '../components/icons'
import { ImageWithFallback } from '../components/ImageWithFallback'
import { OfferRow } from '../components/OfferRow'
import { Price } from '../components/Price'
import { useFetch } from '../hooks/useFetch'
import styles from './ProductPage.module.css'

const releaseDateFormat = new Intl.DateTimeFormat('en-IE', { day: 'numeric', month: 'long', year: 'numeric' })

/** "2021-03-26" → "26 March 2021". Parsed as a local date, so the day can't shift with the time zone. */
function formatReleaseDate(isoDate: string): string {
  return releaseDateFormat.format(new Date(`${isoDate}T00:00:00`))
}

// Product details and every seller's offer for it (PLAN.md §1: "Product page + offers" is a deep part).
// Layout follows 04-product-top.png and 05-product-offers.png; on mobile the offer box moves under the title.
export function ProductPage() {
  // :slug from the route /product/:slug
  const { slug = '' } = useParams()
  const { data: product, error, isLoading } = useFetch<ProductDetail>(`/api/products/${encodeURIComponent(slug)}`)

  if (isLoading) return <ProductSkeleton />

  if (error || !product) {
    const notFound = error?.status === 404
    return (
      <div className={styles.message}>
        <h1>{notFound ? 'Product not found' : 'Something went wrong'}</h1>
        <p>
          {notFound
            ? "This product doesn't exist or was removed."
            : (error?.message ?? 'The product could not be loaded.')}
        </p>
        <Link to="/store" className={styles.messageLink}>
          Browse the store
        </Link>
      </div>
    )
  }

  // Offers come in stock only and cheapest first, so the first one is the best deal.
  const [featured, ...otherOffers] = product.offers

  return (
    <article className={styles.page}>
      <nav aria-label="Breadcrumb" className={styles.breadcrumb}>
        <Link to="/" aria-label="Home">
          <HomeIcon size={18} />
        </Link>
        <span aria-hidden="true">›</span>
        <Link to="/store">Store</Link>
        <span aria-hidden="true">›</span>
        <span className={styles.crumbCurrent} aria-current="page">
          {product.name}
        </span>
      </nav>

      <div className={styles.layout}>
        <section className={styles.hero}>
          <ImageWithFallback
            src={product.coverUrl}
            alt={`${product.name} cover`}
            label={product.name}
            className={styles.cover}
          />

          <div className={styles.heroInfo}>
            <h1 className={styles.title}>{product.name}</h1>

            <ul className={styles.facts}>
              <li>
                <p className={`${styles.factTitle} ${styles.region}`}>{product.region.toLowerCase()}</p>
                <p className={styles.factText}>
                  {product.region === 'GLOBAL' ? 'Can be activated worldwide' : 'Region-locked key'}
                </p>
              </li>
              <li>
                <p className={styles.factTitle}>{product.platform}</p>
                <p className={styles.factText}>
                  Activate/redeem on <span className={styles.highlight}>{product.platform}</span>
                </p>
              </li>
              <li>
                <p className={styles.factTitle}>Digital key</p>
                <p className={styles.factText}>This is a digital edition of the product (CD-KEY)</p>
                <p className={styles.positive}>Instant delivery</p>
              </li>
              <li>
                <p className={styles.factTitle}>Release date</p>
                <p className={styles.factText}>{formatReleaseDate(product.releaseDate)}</p>
              </li>
            </ul>
          </div>
        </section>

        <aside className={styles.aside} aria-label="Featured offer">
          <div className={styles.featured}>
            <p className={styles.featuredLabel}>Featured offer</p>

            {featured ? (
              <>
                <Price
                  size="lg"
                  priceCents={featured.priceCents}
                  msrpCents={product.msrpCents}
                  discountPercent={featured.discountPercent}
                />
                <p className={styles.soldBy}>
                  Sold by <strong>{featured.seller.name}</strong>
                </p>
                <div className={styles.featuredButtons}>
                  <BuyNowButton offer={featured} variant="primary" label={featured.seller.name} />
                  <AddToCartButton offer={featured} variant="primary" label={featured.seller.name} />
                </div>
                <CartLimitHint offer={featured} />
              </>
            ) : (
              <>
                <p className={styles.outOfStock}>Out of stock</p>
                <p className={styles.factText}>No seller has keys for this product right now.</p>
              </>
            )}
          </div>

          {otherOffers.length > 0 && (
            <a href="#offers" className={styles.moreOffers}>
              +{otherOffers.length} other {otherOffers.length === 1 ? 'offer' : 'offers'}
            </a>
          )}
        </aside>

        <div className={styles.details}>
          <section id="offers" className={styles.section}>
            <h2 className={styles.sectionTitle}>
              <span className={styles.positive}>{product.offers.length}</span>{' '}
              {product.offers.length === 1 ? 'offer' : 'offers'}
            </h2>
            {product.offers.length > 0 ? (
              <ul className={styles.offers}>
                {product.offers.map((offer) => (
                  <OfferRow key={offer.id} offer={offer} msrpCents={product.msrpCents} />
                ))}
              </ul>
            ) : (
              <p className={styles.empty}>Out of stock. No sellers have this in stock right now.</p>
            )}
          </section>

          <section className={styles.section}>
            <h2 className={styles.sectionTitle}>Description</h2>
            <p className={styles.description}>{product.description}</p>
          </section>
        </div>
      </div>
    </article>
  )
}

/** Grey blocks in the page's shape while the product loads. */
function ProductSkeleton() {
  return (
    <div className={styles.page} aria-busy="true" aria-label="Loading product">
      <div className={styles.layout}>
        <div className={styles.hero}>
          <div className={`${styles.cover} ${styles.skeleton}`} />
          <div className={styles.heroInfo}>
            <div className={`${styles.skeleton} ${styles.skeletonTitle}`} />
            <div className={`${styles.skeleton} ${styles.skeletonLine}`} />
          </div>
        </div>
        <div className={styles.aside}>
          <div className={`${styles.featured} ${styles.skeletonBox}`} />
        </div>
      </div>
    </div>
  )
}
