import { Link } from 'react-router-dom'
import styles from './Logo.module.css'

// A plain-text recreation of the "eneba" wordmark (not Eneba's logo file). Links to the homepage.
export function Logo({ className }: { className?: string }) {
  return (
    <Link to="/" className={`${styles.logo} ${className ?? ''}`} aria-label="eneba, go to homepage">
      eneba
    </Link>
  )
}
