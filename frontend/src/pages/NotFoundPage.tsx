import { Link } from 'react-router-dom'
import { PlaceholderPage } from './PlaceholderPage'

export function NotFoundPage() {
  return (
    <PlaceholderPage title="Page not found">
      <p>
        This page doesn't exist. <Link to="/">Go to the homepage</Link>
      </p>
    </PlaceholderPage>
  )
}
