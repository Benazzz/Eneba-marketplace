import { useState } from 'react'
import styles from './ImageWithFallback.module.css'

type Props = {
  src: string
  alt: string
  /** Text shown on the placeholder, usually the product name. */
  label: string
  /** Sizes the image and the placeholder alike. */
  className?: string
  loading?: 'lazy' | 'eager'
}

// Cover images are hotlinked from other sites and may break (PLAN.md §11). When one fails to load,
// a generated placeholder with the product name takes its place at the same size.
export function ImageWithFallback({ src, alt, label, className, loading }: Props) {
  // Remembers which src failed instead of a plain boolean, so a new src (e.g. another product
  // on the same page component) gets a fresh try without needing an effect to reset it.
  const [failedSrc, setFailedSrc] = useState<string | null>(null)

  if (failedSrc === src) {
    // The outer div takes the caller's class (size, radius). The inner one paints the placeholder, so a
    // background set by the caller's class can't cover it up.
    return (
      <div className={className} role="img" aria-label={alt || undefined}>
        <div className={styles.placeholder}>
          <span className={styles.label}>{label}</span>
        </div>
      </div>
    )
  }

  return <img src={src} alt={alt} loading={loading} className={className} onError={() => setFailedSrc(src)} />
}
