import { useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { CartIcon, SearchIcon, UserIcon } from './icons'
import { Logo } from './Logo'
import { SearchBox } from './SearchBox'
import styles from './Header.module.css'

// Shown on every page except login/register (see layouts/).
// Wishlist and the language/currency switcher from eneba.com are left out (PLAN.md §1 cut list).
export function Header() {
  // Mobile only: the search bar is hidden behind a search icon, like on eneba.com (CSS decides what shows when).
  const [isMobileSearchOpen, setIsMobileSearchOpen] = useState(false)
  const searchInputRef = useRef<HTMLInputElement>(null)

  // Focus the input once it's visible, so the keyboard opens right away.
  useEffect(() => {
    if (isMobileSearchOpen) searchInputRef.current?.focus()
  }, [isMobileSearchOpen])

  return (
    <header className={styles.header}>
      <div className={`container ${styles.top}`}>
        <Logo />
        <SearchBox
          className={`${styles.search} ${isMobileSearchOpen ? styles.searchOpen : ''}`}
          inputRef={searchInputRef}
          onDone={() => setIsMobileSearchOpen(false)}
        />

        <nav className={styles.actions} aria-label="Cart and account">
          <button
            type="button"
            className={styles.searchToggle}
            aria-label="Search"
            aria-expanded={isMobileSearchOpen}
            onClick={() => setIsMobileSearchOpen((open) => !open)}
          >
            <SearchIcon size={26} />
          </button>

          <Link to="/cart" className={styles.iconLink} aria-label="Cart">
            <CartIcon size={26} />
          </Link>

          <div className={styles.account}>
            {/* On mobile only the icon is shown, so the link gets an aria-label as its name. */}
            <Link to="/login" className={styles.accountLink} aria-label="Log in">
              <UserIcon size={26} />
              <span className={styles.accountText}>Log in</span>
            </Link>
            <span className={`${styles.accountText} ${styles.divider}`} aria-hidden="true">
              |
            </span>
            <Link to="/register" className={`${styles.accountText} ${styles.accountLink}`}>
              Register
            </Link>
          </div>
        </nav>
      </div>

      {/* Sort values from the API (PLAN.md §4), not product data, so these links are fine to keep in the frontend. */}
      <nav className={styles.subnav} aria-label="Store sections">
        <div className={`container ${styles.subnavInner}`}>
          <Link to="/store" className={styles.subnavLink}>
            All games
          </Link>
          <Link to="/store?sort=discount" className={styles.subnavLink}>
            Deals
          </Link>
          <Link to="/store?sort=newest" className={styles.subnavLink}>
            New releases
          </Link>
        </div>
      </nav>
    </header>
  )
}
