// Response shapes from the Symfony API (PLAN.md §4). More shapes are added as their endpoints get built.

/** Every API error has this body, with a matching HTTP status. */
export type ApiErrorBody = {
  error: {
    code: string
    message: string
    /** Per-field validation messages, e.g. { email: "This value is not a valid email address." } */
    fields?: Record<string, string>
  }
}

export type HealthResponse = {
  status: 'ok'
}

/** Product summary used in lists (search dropdown, store grid, homepage rows). Money is in EUR cents. */
export type ProductCard = {
  id: number
  slug: string
  name: string
  coverUrl: string
  platform: string
  region: string
  /** Cheapest offer that still has stock. Null when every offer is sold out. */
  fromPriceCents: number | null
  msrpCents: number
  /** 0–100, null when there is no price. */
  discountPercent: number | null
  /** Offers that still have stock. */
  offerCount: number
}

/** GET /api/search/suggest?q=… */
export type SearchSuggestResponse = {
  items: ProductCard[]
}
