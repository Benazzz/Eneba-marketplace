import { useState } from 'react'
import type { HomeBlockContent } from '../api/types'
import { useFetch } from '../hooks/useFetch'
import type { KnownBlockType } from './blockTypes'
import styles from './HomeBlockSlot.module.css'
import { BlockContent, BlockSkeleton } from './registry'

type Props = {
  id: number
  type: KnownBlockType
  title: string
}

// One homepage block, loaded on its own request (PLAN.md §2). It shows its own skeleton while loading and its
// own error if that request fails, so a broken data source costs only this block, never the page.
export function HomeBlockSlot(props: Props) {
  // "Try again" bumps the key, which makes React throw the loader away and mount a fresh one.
  // Its useFetch then starts from scratch (skeleton included), and useFetch itself needs no reload feature.
  const [attempt, setAttempt] = useState(0)

  return <BlockLoader key={attempt} {...props} onRetry={() => setAttempt((n) => n + 1)} />
}

function BlockLoader({ id, type, title, onRetry }: Props & { onRetry: () => void }) {
  const { data, error } = useFetch<HomeBlockContent>(`/api/home/blocks/${id}`)

  if (error) {
    return (
      <section className={styles.error} role="alert" aria-label={title}>
        <h2 className={styles.errorTitle}>{title}</h2>
        <p>{error.message}</p>
        <button type="button" className={styles.retry} onClick={onRetry}>
          Try again
        </button>
      </section>
    )
  }

  if (!data) return <BlockSkeleton type={type} />

  // Nothing to show (e.g. no active banners): leave no empty gap.
  if (data.items.length === 0) return null

  return <BlockContent block={data} />
}
