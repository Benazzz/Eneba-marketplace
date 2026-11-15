// Tracks whether the API server has answered yet, for the "Waking up the server…" notice (PLAN.md §8).
// The free Render service sleeps after 15 min idle and needs up to a minute to start again.
// Rule: until the server has answered once, a request that is still pending after 3 s turns the notice on.
// The first response of any kind turns it off for good.
//
// This is a tiny external store (subscribe + getSnapshot) so React can read it with useSyncExternalStore.

const SLOW_AFTER_MS = 3000

let awake = false
let waking = false
let pending = 0
let timer: ReturnType<typeof setTimeout> | undefined
const listeners = new Set<() => void>()

function setWaking(value: boolean) {
  if (waking === value) return
  waking = value
  listeners.forEach((listener) => listener())
}

function stopTimer() {
  clearTimeout(timer)
  timer = undefined
  setWaking(false)
}

/** Called by the API client right before a request is sent. */
export function markRequestStart() {
  if (awake) return
  pending++
  // One timer is enough: it measures how long the server has been silent since the first open request.
  timer ??= setTimeout(() => setWaking(true), SLOW_AFTER_MS)
}

/**
 * Called by the API client when a request settles.
 * gotResponse = the server sent an HTTP response (even an error status). false = network error or abort.
 */
export function markRequestEnd(gotResponse: boolean) {
  if (awake) return
  pending--
  if (gotResponse) {
    awake = true
    stopTimer()
  } else if (pending === 0) {
    // Nothing is waiting anymore (e.g. the server is down, not sleeping), so don't leave the notice on.
    stopTimer()
  }
}

export function subscribeToWakeUp(listener: () => void) {
  listeners.add(listener)
  return () => {
    listeners.delete(listener)
  }
}

export function isServerWaking() {
  return waking
}
