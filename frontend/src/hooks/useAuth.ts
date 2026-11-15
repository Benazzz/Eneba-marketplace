import { createContext, useContext } from 'react'
import type { AuthUser } from '../api/types'

// The logged-in user and the actions that change it. AuthProvider (components/AuthProvider.tsx) fills it in.
// It lives in its own file because a .tsx file should only export components (fast refresh / lint rule).

export type AuthContextValue = {
  /** null = guest */
  user: AuthUser | null
  /** Throws ApiError (401 invalid_credentials, 422 validation_failed, network_error, …). */
  login: (email: string, password: string) => Promise<void>
  /** Throws ApiError (409 email_taken, 422 validation_failed, …). */
  register: (email: string, password: string) => Promise<void>
  /** Revokes the token on the server (best effort) and forgets the session locally. */
  logout: () => Promise<void>
}

export const AuthContext = createContext<AuthContextValue | null>(null)

/** Like injecting a scoped service in .NET: any component under AuthProvider can read the user. */
export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext)
  if (context === null) throw new Error('useAuth must be used inside <AuthProvider>')
  return context
}
