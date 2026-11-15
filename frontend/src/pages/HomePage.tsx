import { useState } from 'react'
import type { HomeLayout } from '../api/types'
import { PageTitle } from '../components/PageTitle'
import { StatusMessage } from '../components/StatusMessage'
import { isKnownBlockType } from '../home-blocks/blockTypes'
import { HomeBlockSlot } from '../home-blocks/HomeBlockSlot'
import { BlockSkeleton } from '../home-blocks/registry'
import { useFetch } from '../hooks/useFetch'

// The homepage (PLAN.md §2), as in 01-home.png. First the layout (which blocks, in which order), then every
// block fetches its own content. The page itself holds no content; it only lays out what the API lists.
export function HomePage() {
  // Same remount trick as HomeBlockSlot: a new key gives the layout request a fresh start.
  const [attempt, setAttempt] = useState(0)

  return (
    <>
      <PageTitle>Buy game keys at the best price</PageTitle>
      <HomeLayoutLoader key={attempt} onRetry={() => setAttempt((n) => n + 1)} />
    </>
  )
}

function HomeLayoutLoader({ onRetry }: { onRetry: () => void }) {
  const { data, error } = useFetch<HomeLayout>('/api/home')

  // Without the layout there's nothing to place, so this one error covers the page.
  if (error) {
    return (
      <StatusMessage title="Something went wrong" isError action={{ label: 'Try again', onClick: onRetry }}>
        {error.message}
      </StatusMessage>
    )
  }

  // The usual first screen (a hero and two rows) while the layout loads.
  if (!data) {
    return (
      <>
        <BlockSkeleton type="hero_banner" />
        <BlockSkeleton type="best_sellers" />
        <BlockSkeleton type="deals" />
      </>
    )
  }

  return (
    <>
      <h1 className="sr-only">Eneba: buy game keys at the best price</h1>
      {data.blocks.map((block) =>
        isKnownBlockType(block.type) ? (
          <HomeBlockSlot key={block.id} id={block.id} type={block.type} title={block.title} />
        ) : null,
      )}
    </>
  )
}
