import { PageTitle } from '../components/PageTitle'
import { StatusMessage } from '../components/StatusMessage'

// Any URL that matches no route (App.tsx, path="*"). On Vercel, vercel.json sends every path to index.html,
// so unknown URLs reach this page too instead of Vercel's own 404.
export function NotFoundPage() {
  return (
    <>
      <PageTitle>Page not found</PageTitle>
      <StatusMessage title="Page not found" action={{ label: 'Go to the homepage', to: '/' }}>
        This page doesn't exist. It may have been moved, or the link is wrong.
      </StatusMessage>
    </>
  )
}
