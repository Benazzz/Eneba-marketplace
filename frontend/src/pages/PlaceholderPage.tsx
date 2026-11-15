import type { ReactNode } from 'react'
import styles from './PlaceholderPage.module.css'

type Props = {
  title: string
  /** The PLAN.md §10 build step that replaces this placeholder. */
  step?: number
  children?: ReactNode
}

// Temporary page body, so every route can be clicked through before its real page exists.
export function PlaceholderPage({ title, step, children }: Props) {
  return (
    <section className={styles.page}>
      <h1 className={styles.title}>{title}</h1>
      {children}
      {step !== undefined && <p className={styles.note}>Placeholder. This page is built in step {step} of PLAN.md.</p>}
    </section>
  )
}
