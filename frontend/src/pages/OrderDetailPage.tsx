import { useParams } from 'react-router-dom'
import { PlaceholderPage } from './PlaceholderPage'

export function OrderDetailPage() {
  // :id from the route /orders/:id
  const { id } = useParams()

  return (
    <PlaceholderPage title={`Order #${id}`} step={10}>
      <p>Your keys will be shown here.</p>
    </PlaceholderPage>
  )
}
