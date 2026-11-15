import { useEffect, useState, type ReactNode } from 'react'
import { apiFetch, setAuthToken, setUnauthorizedHandler } from '../api/client'
import type { AuthResponse, AuthUser } from '../api/types'
import { AuthContext, type AuthContextValue } from '../hooks/useAuth'

// Keeps the login session (PLAN.md §6). The session ({ token, expiresAt, user }) is saved in localStorage, so a
// page reload keeps the user logged in, and the header can show them right away. It doesn't wait for the API,
// which may take a minute to wake up on the free Render tier.
// A background GET /api/me then checks the token is still valid. If the API rejects it, the session is dropped.

type Session = AuthResponse

const STORAGE_KEY = 'auth'

function isSession(value: unknown): value is Session {
  const session = value as Session
  return (
    typeof session === 'object' &&
    session !== null &&
    typeof session.token === 'string' &&
    typeof session.expiresAt === 'string' &&
    typeof session.user?.id === 'number' &&
    typeof session.user?.email === 'string'
  )
}

/** The saved session, or null if there is none, it's corrupt, or it has expired. */
function readSession(): Session | null {
  try {
    const data: unknown = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? 'null')
    // `> now` is also false for an unparsable date (NaN), so a broken expiresAt counts as expired.
    return isSession(data) && Date.parse(data.expiresAt) > Date.now() ? data : null
  } catch {
    return null // corrupt JSON or storage blocked: behave like a guest
  }
}

function writeSession(session: Session | null) {
  try {
    if (session) localStorage.setItem(STORAGE_KEY, JSON.stringify(session))
    else localStorage.removeItem(STORAGE_KEY)
  } catch {
    // Storage blocked: the login works until the tab closes, it just isn't remembered.
  }
}

// Read once when the app loads, and hand the token to the API client before any component renders.
// (Doing it in an effect would be too late: child components' effects run before their parent's,
// so a page could fetch its orders before the token was set.)
const initialSession = readSession()
setAuthToken(initialSession?.token ?? null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [session, setSession] = useState<Session | null>(initialSession)

  /** The one place the session changes: API client, localStorage and React state stay in step. */
  function apply(next: Session | null) {
    setAuthToken(next?.token ?? null)
    writeSession(next)
    setSession(next)
  }

  useEffect(() => {
    // A 401 on a request that sent our token means it expired or was revoked: log out locally.
    setUnauthorizedHandler(() => apply(null))

    // Another tab logged in or out: follow it. The 'storage' event only fires in the *other* tabs.
    const onStorage = (e: StorageEvent) => {
      if (e.key !== STORAGE_KEY && e.key !== null) return
      const next = readSession()
      setAuthToken(next?.token ?? null)
      setSession(next)
    }
    window.addEventListener('storage', onStorage)

    return () => {
      setUnauthorizedHandler(null)
      window.removeEventListener('storage', onStorage)
    }
  }, [])

  // Check the saved token once on startup. 401 → the handler above logs out.
  // A network error keeps the session: the server may just be asleep, and the token may still be fine.
  useEffect(() => {
    if (!initialSession) return
    const controller = new AbortController()
    apiFetch<AuthUser>('/api/me', { auth: true, signal: controller.signal }).catch(() => {})
    return () => controller.abort()
  }, [])

  async function startSession(path: string, email: string, password: string) {
    // Emails are case- and space-insensitive on the server too. Trimming here keeps "a@b.com " from
    // failing the email format check.
    const next = await apiFetch<AuthResponse>(path, { method: 'POST', body: { email: email.trim(), password } })
    apply(next)
  }

  const value: AuthContextValue = {
    user: session?.user ?? null,
    login: (email, password) => startSession('/api/auth/login', email, password),
    register: (email, password) => startSession('/api/auth/register', email, password),
    logout: async () => {
      try {
        await apiFetch('/api/auth/logout', { method: 'POST', auth: true })
      } catch {
        // Server unreachable: still log out here. The token then simply expires on its own.
      }
      apply(null)
    },
  }

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
