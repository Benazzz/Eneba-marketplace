import type { HomeBlockContent } from '../api/types'
import type { KnownBlockType } from './blockTypes'
import { HeroBanner, HeroBannerSkeleton } from './HeroBanner'
import { ProductRow, ProductRowSkeleton } from './ProductRow'

// Block type → component (PLAN.md §2). The page only knows block ids and types from the layout.
// These two switches pick what to draw for each type.

/** The placeholder for a block whose content is still loading. Only the type is known at that point. */
export function BlockSkeleton({ type }: { type: KnownBlockType }) {
  return type === 'hero_banner' ? <HeroBannerSkeleton /> : <ProductRowSkeleton />
}

/** A loaded block. Checking `block.type` tells TypeScript which kind of items the block has. */
export function BlockContent({ block }: { block: HomeBlockContent }) {
  switch (block.type) {
    case 'hero_banner':
      return <HeroBanner title={block.title} banners={block.items} />
    case 'best_sellers':
    case 'deals':
    case 'new_releases':
      return <ProductRow title={block.title} products={block.items} />
  }
}
