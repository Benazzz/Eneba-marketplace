import { Link } from 'react-router-dom'
import { Logo } from './Logo'
import styles from './Footer.module.css'

// Laid out like eneba.com's footer (link columns on a dark band), but only with links to pages that exist here.
export function Footer() {
  return (
    <footer className={styles.footer}>
      <div className="container">
        <div className={styles.columns}>
          <div className={styles.about}>
            <Logo className={styles.logo} />
            <p className={styles.blurb}>
              A demo digital-key marketplace built for the Eneba internship assignment. Not affiliated with Eneba.
              Payments are simulated, so no real money is charged.
            </p>
          </div>

          <nav aria-labelledby="footer-shop">
            <h2 id="footer-shop" className={styles.heading}>
              Shop
            </h2>
            <ul className={styles.links}>
              <li>
                <Link to="/store">Store</Link>
              </li>
              <li>
                <Link to="/cart">Cart</Link>
              </li>
            </ul>
          </nav>

          <nav aria-labelledby="footer-account">
            <h2 id="footer-account" className={styles.heading}>
              Account
            </h2>
            <ul className={styles.links}>
              <li>
                <Link to="/login">Log in</Link>
              </li>
              <li>
                <Link to="/register">Register</Link>
              </li>
              <li>
                <Link to="/orders">My orders</Link>
              </li>
            </ul>
          </nav>
        </div>

        <p className={styles.legal}>
          © 2026 Eneba marketplace demo. Game names and cover images belong to their respective owners.
        </p>
      </div>
    </footer>
  )
}
