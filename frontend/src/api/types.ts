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

export type SellerSummary = {
  id: number
  name: string
  /** 0–5 */
  rating: number
  verified: boolean
}

/** One seller's offer for a product. Only offers with stock are sent. */
export type Offer = {
  id: number
  priceCents: number
  /** How far this price is below the product's MSRP, 0–100. */
  discountPercent: number
  /** Available keys, at least 1. */
  stock: number
  seller: SellerSummary
}

/** GET /api/products/{slug}. Offers are in stock only, cheapest first, so offers[0] is the featured offer. */
export type ProductDetail = ProductCard & {
  description: string
  /** YYYY-MM-DD */
  releaseDate: string
  offers: Offer[]
}

/** GET /api/search/suggest?q=… */
export type SearchSuggestResponse = {
  items: ProductCard[]
}
