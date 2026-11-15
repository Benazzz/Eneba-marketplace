// The one place that talks to the Symfony API (PLAN.md §2).
// It adds the base URL and the Bearer token, sends and parses JSON, and turns error responses into a thrown ApiError,
// so callers only deal with typed data or one error type.

import type { ApiErrorBody } from './types'
import { markRequestEnd, markRequestStart } from './wakeUp'

const API_URL = import.meta.env.VITE_API_URL

export class ApiError extends Error {
  /** HTTP status, or 0 when no response came back (network error, missing config). */
  readonly status: number
  /** Machine-readable code from the API, e.g. "validation_failed", "out_of_stock", "network_error". */
  readonly code: string
  readonly fields?: Record<string, string>

  constructor(status: number, code: string, message: string, fields?: Record<string, string>) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.code = code
    this.fields = fields
  }
}

// Set by the auth context (build step 9) after login, and cleared on logout.
let authToken: string | null = null

export function setAuthToken(token: string | null) {
  authToken = token
}

type RequestOptions = {
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE'
  /** Any value. It is sent as JSON. */
  body?: unknown
  /** Lets the caller cancel the request (e.g. when a component unmounts). */
  signal?: AbortSignal
}

/**
 * Calls the API and returns the parsed JSON body.
 * Throws ApiError for error statuses and network failures. An abort is rethrown unchanged (as AbortError).
 */
export async function apiFetch<T>(path: string, { method = 'GET', body, signal }: RequestOptions = {}): Promise<T> {
  if (!API_URL) {
    throw new ApiError(0, 'config_error', 'VITE_API_URL is not set')
  }

  const headers: Record<string, string> = { Accept: 'application/json' }
  if (body !== undefined) headers['Content-Type'] = 'application/json'
  if (authToken) headers.Authorization = `Bearer ${authToken}`

  let response: Response
  markRequestStart()
  try {
    response = await fetch(`${API_URL}${path}`, {
      method,
      headers,
      body: body === undefined ? undefined : JSON.stringify(body),
      signal,
    })
  } catch (err) {
    markRequestEnd(false)
    if (err instanceof DOMException && err.name === 'AbortError') throw err
    // fetch only rejects when no response arrived: server down, offline, or blocked by CORS.
    throw new ApiError(0, 'network_error', 'Could not reach the server. Please try again.')
  }
  markRequestEnd(true)

  // 204 No Content (e.g. logout) has no body to parse.
  if (response.status === 204) return undefined as T

  const data: unknown = await response.json().catch(() => null)

  if (!response.ok) {
    const error = (data as ApiErrorBody | null)?.error
    throw new ApiError(
      response.status,
      error?.code ?? 'http_error',
      error?.message ?? `Request failed (HTTP ${response.status})`,
      error?.fields,
    )
  }
  if (data === null) {
    throw new ApiError(response.status, 'invalid_response', 'The server sent an invalid response.')
  }

  // The API's JSON is trusted to match T. TypeScript can't check data that arrives at runtime.
  return data as T
}
