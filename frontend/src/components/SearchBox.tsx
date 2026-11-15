import { SearchIcon } from './icons'
import styles from './SearchBox.module.css'

// Visual placeholder only. Build step 6 adds the live search with the dropdown.
export function SearchBox({ className }: { className?: string }) {
  return (
    <form role="search" className={`${styles.box} ${className ?? ''}`} onSubmit={(e) => e.preventDefault()}>
      <SearchIcon size={26} className={styles.icon} />
      <input
        type="search"
        className={styles.input}
        placeholder="Search for games, top-ups and more"
        aria-label="Search for games, top-ups and more"
      />
    </form>
  )
}
