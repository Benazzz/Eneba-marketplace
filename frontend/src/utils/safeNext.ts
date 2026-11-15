/**
 * Where to go after login: the ?next= value, but only if it's a path inside this app.
 * Without this check, a link like /login?next=https://evil.example would send the user to another
 * site right after they typed their password (an "open redirect").
 * "//evil.example" is also rejected: browsers read it as a full address on another site.
 */
export function safeNext(next: string | null): string {
  if (next && next.startsWith('/') && !next.startsWith('//') && !next.startsWith('/\\')) return next
  return '/'
}
