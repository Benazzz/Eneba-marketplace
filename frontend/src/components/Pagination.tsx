import { Link } from 'react-router-dom'
import { ChevronDownIcon } from './icons'
import styles from './Pagination.module.css'

type Props = {
  page: number
  pages: number
  /** The link for a page number. */
  hrefFor: (page: number) => string
}

/**
 * The page numbers to show: the first, the last, and the current one with a neighbour on each side.
 * Gaps become 'gap' (shown as "…"). E.g. page 6 of 12 → [1, gap, 5, 6, 7, gap, 12].
 */
function pageItems(page: number, pages: number): (number | 'gap')[] {
  const shown = [...new Set([1, page - 1, page, page + 1, pages])]
    .filter((p) => p >= 1 && p <= pages)
    .sort((a, b) => a - b)

  const items: (number | 'gap')[] = []
  shown.forEach((p, i) => {
    const previous = shown[i - 1]
    // A gap of exactly one page shows that page instead of "…", which would take the same space.
    if (previous !== undefined && p - previous === 2) items.push(previous + 1)
    else if (previous !== undefined && p - previous > 2) items.push('gap')
    items.push(p)
  })
  return items
}

// Page links under the store grid. Real links (not buttons), so the URL holds the page and
// middle-click opens a page in a new tab.
export function Pagination({ page, pages, hrefFor }: Props) {
  if (pages <= 1) return null

  return (
    <nav className={styles.pagination} aria-label="Pages">
      {page > 1 ? (
        <Link to={hrefFor(page - 1)} className={styles.arrow} aria-label="Previous page">
          <ChevronDownIcon size={20} className={styles.prev} />
        </Link>
      ) : (
        <span className={`${styles.arrow} ${styles.disabled}`} aria-hidden="true">
          <ChevronDownIcon size={20} className={styles.prev} />
        </span>
      )}

      {pageItems(page, pages).map((item, i) =>
        item === 'gap' ? (
          <span key={`gap-${i}`} className={styles.gap} aria-hidden="true">
            …
          </span>
        ) : (
          <Link
            key={item}
            to={hrefFor(item)}
            className={`${styles.page} ${item === page ? styles.current : ''}`}
            aria-current={item === page ? 'page' : undefined}
            aria-label={`Page ${item}`}
          >
            {item}
          </Link>
        ),
      )}

      {page < pages ? (
        <Link to={hrefFor(page + 1)} className={styles.arrow} aria-label="Next page">
          <ChevronDownIcon size={20} className={styles.next} />
        </Link>
      ) : (
        <span className={`${styles.arrow} ${styles.disabled}`} aria-hidden="true">
          <ChevronDownIcon size={20} className={styles.next} />
        </span>
      )}
    </nav>
  )
}
