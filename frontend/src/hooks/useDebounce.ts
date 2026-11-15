import { useEffect, useState } from 'react'

// Returns `value`, but only after it has stopped changing for `delayMs`.
// Used by the search box so a request goes out when the user pauses typing, not on every key.
export function useDebounce<T>(value: T, delayMs: number): T {
  const [debounced, setDebounced] = useState(value)

  useEffect(() => {
    // Each change restarts the timer. The cleanup cancels the previous one.
    const timer = setTimeout(() => setDebounced(value), delayMs)
    return () => clearTimeout(timer)
  }, [value, delayMs])

  return debounced
}
