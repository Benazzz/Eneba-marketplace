import { Outlet } from 'react-router-dom'
import { Logo } from '../components/Logo'
import styles from './AuthLayout.module.css'

// Login and register have their own minimal layout, like eneba.com (docs/design-reference/10-login.png):
// a deep purple page with the wordmark, a big greeting on the left, the form card on the right,
// and a one-line footer.
export function AuthLayout() {
  return (
    <div className={styles.layout}>
      <header className={styles.header}>
        <Logo />
      </header>
      <main className={styles.main}>
        {/* Decorative, so screen readers skip it. The form card has the real page heading. */}
        <p className={styles.greeting} aria-hidden="true">
          Hi!
          <br />
          It’s nice to see you!
        </p>
        <Outlet />
      </main>
      <footer className={styles.footer}>© 2026 Eneba marketplace demo. Not affiliated with Eneba.</footer>
    </div>
  )
}
