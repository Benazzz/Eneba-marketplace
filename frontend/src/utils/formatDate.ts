// Order dates come from the API as ISO 8601 timestamps. Shown in the browser's time zone, e.g. "15 Nov 2026, 17:45".
const dateTime = new Intl.DateTimeFormat('en-IE', { dateStyle: 'medium', timeStyle: 'short' })

export function formatDateTime(iso: string): string {
  return dateTime.format(new Date(iso))
}
