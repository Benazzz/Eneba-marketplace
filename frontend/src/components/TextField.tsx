import styles from './TextField.module.css'

type Props = {
  /** Also used to link the error message to the input for screen readers. */
  id: string
  /** Shown as the placeholder (like eneba.com) and as the hidden <label>. */
  label: string
  type?: 'text' | 'email' | 'password'
  value: string
  onChange: (value: string) => void
  /** Browser autofill hint, e.g. "email", "current-password", "new-password". */
  autoComplete?: string
  autoFocus?: boolean
  /** Which on-screen keyboard phones show, e.g. "numeric" for card numbers. */
  inputMode?: 'text' | 'numeric' | 'email'
  maxLength?: number
  error?: string
}

// A dark input (login/register card, checkout card form), with its error message underneath.
export function TextField({
  id,
  label,
  type = 'text',
  value,
  onChange,
  autoComplete,
  autoFocus,
  inputMode,
  maxLength,
  error,
}: Props) {
  const errorId = `${id}-error`

  return (
    <div className={styles.field}>
      {/* The placeholder disappears while typing, so a real (visually hidden) label names the field. */}
      <label htmlFor={id} className="sr-only">
        {label}
      </label>
      <input
        id={id}
        name={id}
        type={type}
        value={value}
        placeholder={label}
        autoComplete={autoComplete}
        autoFocus={autoFocus}
        inputMode={inputMode}
        maxLength={maxLength}
        onChange={(e) => onChange(e.target.value)}
        className={`${styles.input} ${error ? styles.invalid : ''}`}
        aria-invalid={error ? true : undefined}
        aria-describedby={error ? errorId : undefined}
      />
      {error && (
        <p id={errorId} className={styles.error}>
          {error}
        </p>
      )}
    </div>
  )
}
