import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'

// Wraps the routes that need sign-in (checkout, orders) in App.tsx, like [Authorize] on an ASP.NET controller.
// A guest is sent to the login page, and ?next= brings them back here afterwards.
// This is only for the user experience: the API itself refuses those requests without a valid token.
export function RequireAuth() {
  const { user } = useAuth()
  const { pathname, search } = useLocation()

  if (!user) {
    // replace: the protected page doesn't stay in the history, so "Back" after login doesn't loop.
    return <Navigate to={`/login?next=${encodeURIComponent(pathname + search)}`} replace />
  }

  return <Outlet />
}
