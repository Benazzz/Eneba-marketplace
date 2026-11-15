import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import styles from './StatusMessage.module.css'

/** A link to another page, or a button that runs code (e.g. "Try again"). Both look the same. */
type Action = { label: string; to: string } | { label: string; onClick: () => void }

type Props = {
  title: string
  /** h1 when the message is the whole page, h2 when the page already has its own h1. */
  headingLevel?: 'h1' | 'h2'
  /** Errors are announced by screen readers right away (role="alert"). Empty states are not. */
  isError?: boolean
  action?: Action
  /** The explanation under the title. */
  children?: ReactNode
}

// The one look for "loaded, but there's nothing to show": errors, empty results, not found, 404.
// Messages inside a panel (a failed homepage block, the cart quote) keep their own smaller style.
export function StatusMessage({ title, headingLevel = 'h1', isError = false, action, children }: Props) {
  const Heading = headingLevel

  return (
    <div className={styles.message} role={isError ? 'alert' : undefined}>
      <Heading className={styles.title}>{title}</Heading>
      {children && <p className={styles.text}>{children}</p>}
      {action &&
        ('to' in action ? (
          <Link to={action.to} className={styles.action}>
            {action.label}
          </Link>
        ) : (
          <button type="button" className={styles.action} onClick={action.onClick}>
            {action.label}
          </button>
        ))}
    </div>
  )
}
