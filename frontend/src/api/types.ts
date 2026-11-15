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

/** GET /api/me, and the `user` part of a login/register response. */
export type AuthUser = {
  id: number
  email: string
}

/** POST /api/auth/login and /api/auth/register. The token is shown only once, so the client keeps it. */
export type AuthResponse = {
  token: string
  /** ISO 8601, 7 days after login */
  expiresAt: string
  user: AuthUser
}

/** GET /api/search/suggest?q=… */
export type SearchSuggestResponse = {
  items: ProductCard[]
}

/** One line of POST /api/cart/quote, priced by the backend. */
export type CartQuoteLine = {
  offer: {
    id: number
    priceCents: number
    discountPercent: number
    seller: SellerSummary
  }
  product: {
    id: number
    slug: string
    name: string
    coverUrl: string
    platform: string
    region: string
  }
  /** What the cart asked for. It can be above `available`. */
  quantity: number
  lineTotalCents: number
  /** Keys in stock right now. 0 = sold out. */
  available: number
}

/** POST /api/cart/quote. Offers that don't exist are left out of `lines`. */
export type CartQuote = {
  lines: CartQuoteLine[]
  totalCents: number
}

/** POST /api/checkout (201) and GET /api/orders/{id}. */
export type Order = {
  id: number
  /** ISO 8601 */
  createdAt: string
  totalCents: number
  cardLast4: string
  items: {
    productName: string
    productSlug: string
    coverUrl: string
    unitPriceCents: number
    quantity: number
    keys: string[]
  }[]
}

/** One row of GET /api/orders. */
export type OrderSummary = {
  id: number
  createdAt: string
  totalCents: number
  items: { productName: string; quantity: number }[]
}

export type OrdersResponse = {
  items: OrderSummary[]
}
