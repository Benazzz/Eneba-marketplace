import { useEffect, useState } from 'react'
import { ApiError, apiFetch } from '../api/client'

// Loads JSON from a GET endpoint when the component mounts and again whenever `path` changes.
// Pass null to skip the request (e.g. a search box with too few characters).

type FetchResult<T> = {
  /** The path this result belongs to, so a result for an old path is never shown for a new one. */
  path: string
  data: T | null
  error: ApiError | null
}

export function useFetch<T>(path: string | null) {
  const [result, setResult] = useState<FetchResult<T> | null>(null)

  useEffect(() => {
    if (path === null) return

    // Cancels the request when the path changes or the component unmounts (StrictMode also mounts twice in dev).
    const controller = new AbortController()

    apiFetch<T>(path, { signal: controller.signal })
      .then((data) => {
        if (!controller.signal.aborted) setResult({ path, data, error: null })
      })
      .catch((err: unknown) => {
        if (controller.signal.aborted) return
        const error = err instanceof ApiError ? err : new ApiError(0, 'unknown_error', String(err))
        setResult({ path, data: null, error })
      })

    return () => controller.abort()
  }, [path])

  // Loading isn't stored as state. It's worked out during render: there's no result for the current path yet.
  // That way the effect never has to call setState synchronously to say "loading started".
  const current = result?.path === path ? result : null

  return {
    data: current?.data ?? null,
    error: current?.error ?? null,
    isLoading: path !== null && current === null,
  }
}
