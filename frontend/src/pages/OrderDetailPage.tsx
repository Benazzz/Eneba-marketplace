import { Link, useLocation, useParams } from 'react-router-dom'
import type { Order } from '../api/types'
import { CheckoutSteps } from '../components/CheckoutSteps'
import { ImageWithFallback } from '../components/ImageWithFallback'
import { KeyCode } from '../components/KeyCode'
import { useFetch } from '../hooks/useFetch'
import { formatDateTime } from '../utils/formatDate'
import { formatPrice } from '../utils/formatPrice'
import styles from './Orders.module.css'

// One order with its keys (step 3, "Get your product"). Reached right after checkout, or later from /orders.
export function OrderDetailPage() {
  // :id from the route /orders/:id
  const { id = '' } = useParams()
  // Set by the checkout page, so the "thank you" header only shows right after buying.
  const justPlaced = (useLocation().state as { justPlaced?: boolean } | null)?.justPlaced === true
  const { data: order, error, isLoading } = useFetch<Order>(`/api/orders/${encodeURIComponent(id)}`, { auth: true })

  if (isLoading) {
    return (
      <div className={styles.page} aria-busy="true" aria-label="Loading order">
        <div className={styles.skeleton} />
      </div>
    )
  }

  if (error || !order) {
    const notFound = error?.status === 404
    return (
      <div className={styles.page}>
        <h1 className={styles.title}>{notFound ? 'Order not found' : 'Something went wrong'}</h1>
        <p className={styles.muted}>
          {notFound ? "This order doesn't exist, or it belongs to another account." : error?.message}
        </p>
        <Link to="/orders" className={styles.link}>
          Back to my orders
        </Link>
      </div>
    )
  }

  return (
    <div className={styles.page}>
      {justPlaced && (
        <>
          <CheckoutSteps current={3} />
          <div className={styles.thanks} role="status">
            <h1>Thank you for your purchase!</h1>
            <p>Your keys are ready below. You can find them again any time in My orders.</p>
          </div>
        </>
      )}

      <header className={styles.orderHeader}>
        {justPlaced ? <h2 className={styles.title}>Order #{order.id}</h2> : <h1 className={styles.title}>Order #{order.id}</h1>}
        <p className={styles.muted}>
          {formatDateTime(order.createdAt)} · Paid with card •••• {order.cardLast4} · Total{' '}
          <strong className={styles.amount}>{formatPrice(order.totalCents)}</strong>
        </p>
      </header>

      <ul className={styles.items}>
        {/* The same product can be bought from two sellers in one order, so the slug alone isn't a unique key. */}
        {order.items.map((item, index) => (
          <li key={`${index}-${item.productSlug}`} className={styles.item}>
            <Link to={`/product/${item.productSlug}`} tabIndex={-1} aria-hidden="true">
              <ImageWithFallback src={item.coverUrl} alt="" label={item.productName} className={styles.cover} />
            </Link>
            <div className={styles.itemBody}>
              <Link to={`/product/${item.productSlug}`} className={styles.itemName}>
                {item.productName}
              </Link>
              <p className={styles.muted}>
                {item.quantity} × {formatPrice(item.unitPriceCents)}
              </p>
              <p className={styles.keysLabel}>{item.keys.length === 1 ? 'Your key' : 'Your keys'}</p>
              <ul className={styles.keys}>
                {item.keys.map((code) => (
                  <li key={code}>
                    <KeyCode code={code} />
                  </li>
                ))}
              </ul>
            </div>
          </li>
        ))}
      </ul>

      <Link to="/orders" className={styles.link}>
        ← All my orders
      </Link>
    </div>
  )
}
