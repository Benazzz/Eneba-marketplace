import { Link } from 'react-router-dom'
import { PlaceholderPage } from './PlaceholderPage'

export function RegisterPage() {
  return (
    <PlaceholderPage title="Create account" step={9}>
      <p>
        Already have an account? <Link to="/login">Log in</Link>
      </p>
    </PlaceholderPage>
  )
}
