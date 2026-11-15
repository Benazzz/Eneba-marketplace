import { Link } from 'react-router-dom'
import type { OrdersResponse } from '../api/types'
import { PageTitle } from '../components/PageTitle'
import { StatusMessage } from '../components/StatusMessage'
import { useFetch } from '../hooks/useFetch'
import { formatDateTime } from '../utils/formatDate'
import { formatPrice } from '../utils/formatPrice'
import styles from './Orders.module.css'

// The signed-in user's order history, newest first. The keys themselves are on each order's page.
export function OrdersPage() {
  const { data, error, isLoading } = useFetch<OrdersResponse>('/api/orders', { auth: true })

  return (
    <div className={styles.page}>
      <PageTitle>My orders</PageTitle>
      <h1 className={styles.title}>My orders</h1>

      {isLoading ? (
        <div className={`${styles.skeleton} skeleton`} aria-busy="true" aria-label="Loading orders" />
      ) : error || !data ? (
        <StatusMessage
          title="Something went wrong"
          headingLevel="h2"
          isError
          action={{ label: 'Browse the store', to: '/store' }}
        >
          {error?.message ?? 'Your orders could not be loaded.'}
        </StatusMessage>
      ) : data.items.length === 0 ? (
        <StatusMessage title="No orders yet" headingLevel="h2" action={{ label: 'Browse the store', to: '/store' }}>
          You haven't bought anything yet. Your keys will show up here after your first purchase.
        </StatusMessage>
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
