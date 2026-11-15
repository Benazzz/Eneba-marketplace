import { Route, Routes } from 'react-router-dom'
import type { HealthResponse } from './api/types'
import { RequireAuth } from './components/RequireAuth'
import { WakeUpNotice } from './components/WakeUpNotice'
import { useFetch } from './hooks/useFetch'
import { AuthLayout } from './layouts/AuthLayout'
import { MainLayout } from './layouts/MainLayout'
import { CartPage } from './pages/CartPage'
import { CheckoutPage } from './pages/CheckoutPage'
import { HomePage } from './pages/HomePage'
import { LoginPage } from './pages/LoginPage'
import { NotFoundPage } from './pages/NotFoundPage'
import { OrderDetailPage } from './pages/OrderDetailPage'
import { OrdersPage } from './pages/OrdersPage'
import { ProductPage } from './pages/ProductPage'
import { RegisterPage } from './pages/RegisterPage'
import { StorePage } from './pages/StorePage'

function App() {
  // Warm-up ping: starts waking the API server (the free Render tier sleeps when idle) as soon as the app opens,
  // even on pages that don't load data yet. The result isn't used. If it's slow, WakeUpNotice appears.
  useFetch<HealthResponse>('/api/health')

  return (
    <>
      {/* Routes without a path only wrap their children in a layout (like nested layouts in ASP.NET). */}
      <Routes>
        <Route element={<MainLayout />}>
          <Route index element={<HomePage />} />
          <Route path="store" element={<StorePage />} />
          <Route path="product/:slug" element={<ProductPage />} />
          <Route path="cart" element={<CartPage />} />
          {/* Sign-in required (PLAN.md §6). */}
          <Route element={<RequireAuth />}>
            <Route path="checkout" element={<CheckoutPage />} />
            <Route path="orders" element={<OrdersPage />} />
            <Route path="orders/:id" element={<OrderDetailPage />} />
          </Route>
          <Route path="*" element={<NotFoundPage />} />
        </Route>
        <Route element={<AuthLayout />}>
          <Route path="login" element={<LoginPage />} />
          <Route path="register" element={<RegisterPage />} />
        </Route>
      </Routes>
      <WakeUpNotice />
    </>
  )
}

export default App
