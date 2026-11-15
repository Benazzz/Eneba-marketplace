import { Link } from 'react-router-dom'
import type { OrdersResponse } from '../api/types'
import { useFetch } from '../hooks/useFetch'
import { formatDateTime } from '../utils/formatDate'
import { formatPrice } from '../utils/formatPrice'
import styles from './Orders.module.css'

// The signed-in user's order history, newest first. The keys themselves are on each order's page.
export function OrdersPage() {
  const { data, error, isLoading } = useFetch<OrdersResponse>('/api/orders', { auth: true })

  return (
    <div className={styles.page}>
      <h1 className={styles.title}>My orders</h1>

      {isLoading ? (
        <div className={styles.skeleton} aria-busy="true" aria-label="Loading orders" />
      ) : error || !data ? (
        <p className={styles.muted} role="alert">
          {error?.message ?? 'Your orders could not be loaded.'}
        </p>
      ) : data.items.length === 0 ? (
        <div className={styles.empty}>
          <p className={styles.muted}>You haven't bought anything yet.</p>
          <Link to="/store" className={styles.button}>
            Browse the store
          </Link>
        </div>
      ) : (
        <ul className={styles.orderList}>
          {data.items.map((order) => {
            const keyCount = order.items.reduce((sum, item) => sum + item.quantity, 0)
            return (
              <li key={order.id}>
                <Link to={`/orders/${order.id}`} className={styles.orderRow}>
                  <div className={styles.orderInfo}>
                    <p className={styles.orderNumber}>
                      Order #{order.id} <span className={styles.muted}>· {formatDateTime(order.createdAt)}</span>
                    </p>
                    <p className={styles.orderProducts}>
                      {order.items.map((item) => (item.quantity > 1 ? `${item.productName} ×${item.quantity}` : item.productName)).join(', ')}
                    </p>
                  </div>
                  <div className={styles.orderSide}>
                    <strong className={styles.amount}>{formatPrice(order.totalCents)}</strong>
                    <span className={styles.viewKeys}>
                      View {keyCount === 1 ? 'key' : `${keyCount} keys`} →
                    </span>
                  </div>
                </Link>
              </li>
            )
          })}
        </ul>
      )}
    </div>
  )
}
