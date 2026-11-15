import type { HomeBlockContent } from '../api/types'

export type KnownBlockType = HomeBlockContent['type']

// Every block type this frontend can draw. The backend may add types before the frontend knows them;
// those are skipped instead of breaking the page.
const KNOWN_BLOCK_TYPES: ReadonlySet<string> = new Set<KnownBlockType>([
  'hero_banner',
  'best_sellers',
  'deals',
  'new_releases',
])

/** A type guard: inside `if (isKnownBlockType(t))`, TypeScript treats t as a KnownBlockType. */
export function isKnownBlockType(type: string): type is KnownBlockType {
  return KNOWN_BLOCK_TYPES.has(type)
}
