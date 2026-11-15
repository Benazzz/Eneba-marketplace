import { Link } from 'react-router-dom'
import { CartIcon, UserIcon } from './icons'
import { Logo } from './Logo'
import { SearchBox } from './SearchBox'
import styles from './Header.module.css'

// Shown on every page except login/register (see layouts/).
// Wishlist and the language/currency switcher from eneba.com are left out (PLAN.md §1 cut list).
export function Header() {
  return (
    <header className={styles.header}>
      <div className={`container ${styles.top}`}>
        <Logo />
        <SearchBox className={styles.search} />

        <nav className={styles.actions} aria-label="Cart and account">
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
