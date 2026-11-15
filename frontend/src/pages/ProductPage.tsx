import { useParams } from 'react-router-dom'
import { PlaceholderPage } from './PlaceholderPage'

export function ProductPage() {
  // :slug from the route /product/:slug
  const { slug } = useParams()

  return (
    <PlaceholderPage title="Product" step={8}>
      <p>Slug: {slug}</p>
    </PlaceholderPage>
  )
}
