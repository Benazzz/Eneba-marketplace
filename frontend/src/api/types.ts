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
