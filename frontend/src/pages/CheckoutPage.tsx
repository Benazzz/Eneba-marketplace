import { useState, type FormEvent } from 'react'
import { Link, Navigate, useNavigate } from 'react-router-dom'
import { ApiError, apiFetch } from '../api/client'
import type { Order } from '../api/types'
import { CartLine } from '../components/CartLine'
import { CheckoutSteps } from '../components/CheckoutSteps'
import { TextField } from '../components/TextField'
import { useAuth } from '../hooks/useAuth'
import { useCart } from '../hooks/useCart'
import { useCartQuote } from '../hooks/useCartQuote'
import { clearField, NO_ERRORS, toFormErrors, type FormErrors } from '../utils/formErrors'
import { formatPrice } from '../utils/formatPrice'
import flow from './CheckoutFlow.module.css'
import styles from './CheckoutPage.module.css'

/** The simulated payment's test cards (PLAN.md §7). Other numbers are refused by the API. */
const TEST_CARDS = {
  approved: { number: '4242 4242 4242 4242', label: 'Payment succeeds' },
  declined: { number: '4000 0000 0000 0002', label: 'Card is declined' },
}

const digitsOnly = (value: string, max: number) => value.replace(/\D/g, '').slice(0, max)

/** "4242424242424242" → "4242 4242 4242 4242" (only for display; the API gets digits only) */
const groupCardNumber = (digits: string) => digits.replace(/(\d{4})(?=\d)/g, '$1 ')

/** "1230" → "12/30". The slash is added while typing, so the buyer only types digits. */
const formatExpiry = (digits: string) => (digits.length > 2 ? `${digits.slice(0, 2)}/${digits.slice(2)}` : digits)

// Step 2 of the purchase flow (09-checkout-payment.png), sign-in required. The cart is quoted again here, then
// POST /api/checkout places the order. The card details live only in this component's state: they are never
// saved in the browser, and the API keeps only the last 4 digits.
export function CheckoutPage() {
  const navigate = useNavigate()
  const { user } = useAuth()
  const { items, quantityOf, clear } = useCart()
  const { quote, error: quoteError, isUpdating, canCheckout, retry } = useCartQuote()

  const [cardNumber, setCardNumber] = useState('') // digits only
  const [expiry, setExpiry] = useState('') // digits only, MMYY
  const [cvc, setCvc] = useState('')
  const [name, setName] = useState('')
  const [errors, setErrors] = useState<FormErrors>(NO_ERRORS)
  // Stays true after a successful payment, so the emptied cart doesn't trigger the redirect below.
  const [isPaying, setIsPaying] = useState(false)

  if (items.length === 0 && !isPaying) return <Navigate to="/cart" replace />

  function editField(field: string, setValue: (value: string) => void, value: string) {
    setValue(value)
    setErrors((current) => ({ ...clearField(current, `card.${field}`), message: null }))
  }

  function fillTestCard(number: string) {
    setCardNumber(digitsOnly(number, 19))
    setExpiry('1230')
    setCvc('123')
    if (name.trim() === '') setName('Test Buyer')
    setErrors(NO_ERRORS)
  }

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    if (!canCheckout || isPaying) return

    setErrors(NO_ERRORS)
    setIsPaying(true)
    try {
      const order = await apiFetch<Order>('/api/checkout', {
        method: 'POST',
        auth: true,
        body: { items, card: { number: cardNumber, expiry: formatExpiry(expiry), cvc, name: name.trim() } },
      })
      // Paid: the keys are on the order page. The cart is done.
      navigate(`/orders/${order.id}`, { replace: true, state: { justPlaced: true } })
      clear()
    } catch (err) {
      setIsPaying(false)
      if (err instanceof ApiError && err.status === 409) {
        // Out of stock: nothing was charged. Back to the cart, which shows which line to fix.
        navigate('/cart', { state: { notice: err.message } })
        return
      }
      if (err instanceof ApiError && err.status === 401) return // signed out: RequireAuth sends the buyer to log in

      // 402 declined, network errors, and card field errors (422). The cart stays as it is.
      const formErrors = toFormErrors(err)
      const hasCardFields = Object.keys(formErrors.fields).some((field) => field.startsWith('card.'))
      setErrors(
        hasCardFields || Object.keys(formErrors.fields).length === 0
          ? formErrors
          : { fields: {}, message: 'Your cart could not be checked out. Please go back to your cart and try again.' },
      )
    }
  }

  const lines = quote?.lines.filter((line) => quantityOf(line.offer.id) > 0) ?? []
  const blocked = !isUpdating && !quoteError && !canCheckout

  return (
    <div className={flow.page}>
      <CheckoutSteps current={2} />

      <div className={flow.layout}>
        <section className={flow.panel}>
          <h1 className={flow.panelTitle}>Credit or debit card</h1>

          <div className={styles.testMode}>
            <p>
              <strong>Test mode.</strong> No real money is charged. Use a test card:
            </p>
            <ul className={styles.testCards}>
              {Object.values(TEST_CARDS).map((card) => (
                <li key={card.number}>
                  <button type="button" className={styles.testCard} onClick={() => fillTestCard(card.number)}>
                    <code>{card.number}</code>
                    <span>{card.label}</span>
                  </button>
                </li>
              ))}
            </ul>
          </div>

          {/* noValidate: the API checks the fields, and its messages are shown in our own style. */}
          <form id="payment-form" className={styles.form} onSubmit={handleSubmit} noValidate>
            <TextField
              id="card-number"
              label="Card number"
              autoComplete="cc-number"
              inputMode="numeric"
              maxLength={23}
              value={groupCardNumber(cardNumber)}
              onChange={(value) => editField('number', setCardNumber, digitsOnly(value, 19))}
              error={errors.fields['card.number']}
            />
            <div className={styles.twoColumns}>
              <TextField
                id="card-expiry"
                label="MM/YY"
                autoComplete="cc-exp"
                inputMode="numeric"
                maxLength={5}
                value={formatExpiry(expiry)}
                onChange={(value) => editField('expiry', setExpiry, digitsOnly(value, 4))}
                error={errors.fields['card.expiry']}
              />
              <TextField
                id="card-cvc"
                label="CVC"
                autoComplete="cc-csc"
                inputMode="numeric"
                maxLength={4}
                value={cvc}
                onChange={(value) => editField('cvc', setCvc, digitsOnly(value, 4))}
                error={errors.fields['card.cvc']}
              />
            </div>
            <TextField
              id="card-name"
              label="Name on card"
              autoComplete="cc-name"
              value={name}
              onChange={(value) => editField('name', setName, value)}
              error={errors.fields['card.name']}
            />
          </form>
        </section>

        <aside className={`${flow.panel} ${flow.summary}`} aria-label="Order summary">
          {quoteError ? (
            <div className={flow.errorBox} role="alert">
              <p>{quoteError.message}</p>
              <button type="button" className={flow.secondaryButton} onClick={retry}>
                Try again
              </button>
            </div>
          ) : quote === null ? (
            <div className={flow.skeletonLine} aria-label="Loading your order" />
          ) : (
            <ul className={`${flow.lines} ${isUpdating ? flow.updating : ''}`}>
              {lines.map((line) => (
                <CartLine key={line.offer.id} line={line} quantity={quantityOf(line.offer.id)} />
              ))}
            </ul>
          )}

          {user && (
            <p className={flow.row}>
              <span>Keys go to</span>
              <span>{user.email}</span>
            </p>
          )}

          {quote && (
            <p className={`${flow.total} ${isUpdating ? flow.updating : ''}`}>
              <span>Total:</span>
              <span className={flow.totalAmount}>{formatPrice(quote.totalCents)}</span>
            </p>
          )}

          {blocked && (
            <div className={flow.errorBox} role="alert">
              <p>Some items in your cart are sold out or have fewer keys left.</p>
              <Link to="/cart" className={flow.secondaryButton}>
                Back to cart
              </Link>
            </div>
          )}

          {errors.message && (
            <p className={styles.payError} role="alert">
              {errors.message}
            </p>
          )}

          {/* form="payment-form" submits the card form, although the button sits in the summary. */}
          <button type="submit" form="payment-form" className={flow.cta} disabled={!canCheckout || isPaying}>
            {isPaying ? 'Processing payment…' : quote ? `Pay ${formatPrice(quote.totalCents)}` : 'Pay'}
          </button>
          <Link to="/cart" className={styles.editCart}>
            Edit cart
          </Link>
        </aside>
      </div>
    </div>
  )
}
