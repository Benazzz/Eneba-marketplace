import { Link } from 'react-router-dom'
import type { Banner } from '../api/types'
import { ImageWithFallback } from '../components/ImageWithFallback'
import styles from './HeroBanner.module.css'

type Props = {
  /** The block's title from the layout. Not shown, but names the region for screen readers. */
  title: string
  banners: Banner[]
}

// The hero area at the top of the homepage, as in 01-home.png: the first banner is the big slide with its
// text and a button, the others are tall tiles beside it. On phones (14-mobile-home.png) they all become
// one strip of tiles that scrolls sideways.
export function HeroBanner({ title, banners }: Props) {
  const [main, ...tiles] = banners

  return (
    <section className={styles.hero} aria-label={title}>
      <Link to={main.linkUrl} className={`${styles.slide} ${styles.main}`}>
        <ImageWithFallback src={main.imageUrl} alt="" label={main.title} className={styles.image} />
        <div className={styles.caption}>
          <h2 className={styles.title}>{main.title}</h2>
          <p className={styles.subtitle}>{main.subtitle}</p>
          <span className={styles.cta}>Shop now</span>
        </div>
      </Link>

      {tiles.map((banner) => (
        <Link key={banner.id} to={banner.linkUrl} className={styles.slide} title={banner.subtitle}>
          <ImageWithFallback src={banner.imageUrl} alt="" label={banner.title} className={styles.image} />
          <div className={styles.caption}>
            <h2 className={styles.title}>{banner.title}</h2>
          </div>
        </Link>
      ))}
    </section>
  )
}

/** Same size as the hero, so nothing below it jumps when it arrives. */
export function HeroBannerSkeleton() {
  return <div className={`${styles.hero} ${styles.skeleton} skeleton`} aria-hidden="true" />
}
