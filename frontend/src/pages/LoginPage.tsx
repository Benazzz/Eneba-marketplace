import { Link } from 'react-router-dom'
import { PlaceholderPage } from './PlaceholderPage'

export function LoginPage() {
  return (
    <PlaceholderPage title="Log in" step={9}>
      <p>
        New user? <Link to="/register">Create account</Link>
      </p>
    </PlaceholderPage>
  )
}
