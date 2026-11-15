const SITE_NAME = 'Eneba marketplace'

// The text in the browser tab. React 19 moves a <title> rendered anywhere into <head> (and takes it out again
// when the page unmounts), so no package like react-helmet is needed. index.html keeps the plain site name
// for the first paint. React wants a single string inside <title>, so the text is built first.
export function PageTitle({ children }: { children: string }) {
  return <title>{`${children} | ${SITE_NAME}`}</title>
}
