import { useSyncExternalStore } from 'react'
import { isServerWaking, subscribeToWakeUp } from '../api/wakeUp'
import styles from './WakeUpNotice.module.css'

// Shown when the first API request takes more than 3 s (the free Render server is starting up). See api/wakeUp.ts.
export function WakeUpNotice() {
  // useSyncExternalStore re-renders this component whenever the store outside React changes.
  const waking = useSyncExternalStore(subscribeToWakeUp, isServerWaking)

  // The live region always stays in the DOM, so screen readers announce the text when it appears.
  return (
    <div role="status" className={waking ? styles.notice : undefined}>
      {waking && (
        <>
          <span className={styles.spinner} aria-hidden="true" />
          <p>
            <strong>Waking up the server…</strong> The free hosting sleeps when idle, so this can take up to a minute.
          </p>
        </>
      )}
    </div>
  )
}
