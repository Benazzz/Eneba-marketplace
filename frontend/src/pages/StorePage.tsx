import { useSearchParams } from 'react-router-dom'
import { PlaceholderPage } from './PlaceholderPage'

export function StorePage() {
  const [searchParams] = useSearchParams()
  const q = searchParams.get('q')

  // Until step 12, it shows the search term, so the header's "See all results" link can be checked.
  return (
    <PlaceholderPage title="Store" step={12}>
      {q && <p>Results for "{q}"</p>}
    </PlaceholderPage>
  )
}
