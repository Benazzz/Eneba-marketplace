import { useEffect, useState } from 'react'

// Temporary deploy probe: proves the frontend can reach the API (URL + CORS).
// Replaced by the real app shell and the api.ts wrapper in build step 5.

type Health =
  | { state: 'checking' }
  | { state: 'ok' }
  | { state: 'error'; message: string }

const API_URL = import.meta.env.VITE_API_URL

function App() {
  // A missing env var is known before the first render, so it is set here, not in the effect.
  const [health, setHealth] = useState<Health>(() =>
    API_URL ? { state: 'checking' } : { state: 'error', message: 'VITE_API_URL is not set' },
  )

  useEffect(() => {
    if (!API_URL) return

    // Abort the request if the component unmounts (StrictMode mounts twice in dev).
    const controller = new AbortController()

    fetch(`${API_URL}/api/health`, { signal: controller.signal })
      .then(async (res) => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`)
        const body: { status?: string } = await res.json()
        if (body.status !== 'ok') throw new Error('Unexpected response')
        setHealth({ state: 'ok' })
      })
      .catch((err: unknown) => {
        if (controller.signal.aborted) return
        // A CORS rejection shows up here as a generic network error ("Failed to fetch").
        setHealth({ state: 'error', message: err instanceof Error ? err.message : String(err) })
      })

    return () => controller.abort()
  }, [])

  return (
    <main>
      <h1>Eneba marketplace</h1>
      <p>
        API:{' '}
        {health.state === 'checking' && 'checking…'}
        {health.state === 'ok' && 'ok'}
        {health.state === 'error' && `error (${health.message})`}
      </p>
    </main>
  )
}

export default App
