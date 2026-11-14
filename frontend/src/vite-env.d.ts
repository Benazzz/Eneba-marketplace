// Types for our own VITE_ env vars (Vite's built-in ones come from "vite/client" in tsconfig.app.json).
interface ImportMetaEnv {
  /** Base URL of the Symfony API, without a trailing slash. */
  readonly VITE_API_URL?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
