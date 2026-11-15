import { useEffect, useRef, useState } from 'react'
import { CheckIcon, CopyIcon } from './icons'
import styles from './KeyCode.module.css'

const COPIED_FEEDBACK_MS = 1500

// One delivered key with a Copy button. If the browser won't allow clipboard access (e.g. not HTTPS),
// the key text is selected instead, so the buyer can press Ctrl+C.
export function KeyCode({ code }: { code: string }) {
  const [copied, setCopied] = useState(false)
  const codeRef = useRef<HTMLElement>(null)

  useEffect(() => {
    if (!copied) return
    const timer = setTimeout(() => setCopied(false), COPIED_FEEDBACK_MS)
    return () => clearTimeout(timer)
  }, [copied])

  async function copy() {
    try {
      await navigator.clipboard.writeText(code)
      setCopied(true)
    } catch {
      const element = codeRef.current
      if (element) window.getSelection()?.selectAllChildren(element)
    }
  }

  return (
    <div className={styles.key}>
      <code ref={codeRef} className={styles.code}>
        {code}
      </code>
      <button type="button" className={styles.copy} onClick={copy} aria-label={`Copy key ${code}`}>
        {copied ? <CheckIcon size={18} /> : <CopyIcon size={18} />}
        <span>{copied ? 'Copied' : 'Copy'}</span>
      </button>
      <span className="sr-only" role="status">
        {copied ? 'Key copied to the clipboard' : ''}
      </span>
    </div>
  )
}
