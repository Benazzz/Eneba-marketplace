import { Outlet } from 'react-router-dom'
import { Footer } from '../components/Footer'
import { Header } from '../components/Header'
import styles from './MainLayout.module.css'

// Layout for every page except login/register. <Outlet /> is where React Router renders the matched child route
// (like @RenderBody() in an ASP.NET layout).
export function MainLayout() {
  return (
    <div className={styles.layout}>
      <Header />
      <main className={`container ${styles.main}`}>
        <Outlet />
      </main>
      <Footer />
    </div>
  )
}
