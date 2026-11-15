import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import type { Offer } from '../api/types'
import { useCart } from '../hooks/useCart'
import { CartPlusIcon } from './icons'
import styles from './CartButtons.module.css'

const ADDED_FEEDBACK_MS = 1500

type Props = {
  offer: Offer
  /** primary = big yellow (featured offer), outline = white border (offer rows) */
  variant: 'primary' | 'outline'
  /** Says which seller the button is for, since every offer row has the same buttons. */
  label: string
}

/** Puts one key of the offer in the cart (unless it's already there) and opens the cart. */
export function BuyNowButton({ offer, variant, label }: Props) {
  const navigate = useNavigate()
  const { add, quantityOf } = useCart()

  return (
    <button
      type="button"
      className={variant === 'primary' ? styles.buyPrimary : styles.buyOutline}
      aria-label={`Buy now: ${label}`}
      onClick={() => {
        if (quantityOf(offer.id) === 0) add(offer.id, offer.stock)
        navigate('/cart')
      }}
    >
      Buy now
    </button>
  )
}

/**
 * Explains why the add button is disabled: the cart already holds every available key of this offer.
 * Renders nothing while more can be added. The cart doesn't reserve keys, so stock can also drop
 * below what's in the cart (someone else bought them); then the hint says both numbers.
 */
export function CartLimitHint({ offer, className }: { offer: Offer; className?: string }) {
  const { quantityOf } = useCart()
  const inCart = quantityOf(offer.id)

  if (inCart < offer.stock) return null

  let text: string
  if (inCart > offer.stock) text = `${inCart} in your cart, only ${offer.stock} left`
  else if (offer.stock === 1) text = 'The last key is in your cart'
  else text = `All ${offer.stock} in your cart`

  return <p className={`${styles.hint} ${className ?? ''}`}>{text}</p>
}

/** Adds one more key of the offer to the cart, up to its stock. Shows a short "Added" tick afterwards. */
export function AddToCartButton({ offer, variant, label }: Props) {
  const { add, quantityOf } = useCart()
  const [justAdded, setJustAdded] = useState(false)
  const isFull = quantityOf(offer.id) >= offer.stock

  // Hide the tick again after a moment. The cleanup stops the timer if the user clicks again or leaves the page.
  useEffect(() => {
    if (!justAdded) return
    const timer = setTimeout(() => setJustAdded(false), ADDED_FEEDBACK_MS)
    return () => clearTimeout(timer)
  }, [justAdded])

  return (
    <button
      type="button"
      className={`${styles.add} ${variant === 'primary' ? styles.addLarge : ''}`}
      aria-label={isFull ? `All keys are in your cart: ${label}` : `Add to cart: ${label}`}
      title={isFull ? 'All available keys are in your cart' : 'Add to cart'}
      disabled={isFull}
      onClick={() => {
        if (add(offer.id, offer.stock)) setJustAdded(true)
      }}
    >
      {justAdded ? <span className={styles.added}>✓</span> : <CartPlusIcon size={variant === 'primary' ? 24 : 20} />}
      {/* Screen readers hear about the add without moving focus. */}
      <span className="sr-only" role="status">
        {justAdded ? 'Added to cart' : ''}
      </span>
    </button>
  )
}
