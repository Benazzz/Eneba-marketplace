import { CheckIcon } from './icons'
import styles from './CheckoutSteps.module.css'

const STEPS = ['Cart', 'Payment', 'Get your product']

type Props = {
  /** 1 = Cart, 2 = Payment, 3 = Get your product */
  current: 1 | 2 | 3
}

// The "1 Cart — 2 Payment — 3 Get your product" bar from 08-checkout-cart.png and 09-checkout-payment.png.
export function CheckoutSteps({ current }: Props) {
  return (
    <ol className={styles.steps} aria-label="Checkout progress">
      {STEPS.map((label, index) => {
        const step = index + 1
        const state = step < current ? 'done' : step === current ? 'current' : 'upcoming'
        return (
          <li key={label} className={`${styles.step} ${styles[state]}`} aria-current={state === 'current' ? 'step' : undefined}>
            <span className={styles.marker} aria-hidden="true">
              {state === 'done' ? <CheckIcon size={16} /> : step}
            </span>
            <span className={styles.label}>{label}</span>
          </li>
        )
      })}
    </ol>
  )
}
