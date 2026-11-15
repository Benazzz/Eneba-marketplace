import { useState, type FormEvent } from 'react'
import { Link, Navigate, useLocation, useSearchParams } from 'react-router-dom'
import { TextField } from '../components/TextField'
import { useAuth } from '../hooks/useAuth'
import { clearField, NO_ERRORS, toFormErrors, type FormErrors } from '../utils/formErrors'
import { safeNext } from '../utils/safeNext'
import styles from './AuthPage.module.css'

/** Shown as a live hint only. The real check is the API's (RegisterRequest), which sends its own message. */
const MIN_PASSWORD_LENGTH = 8

// docs/design-reference/11-register.png. Cut: social logins, the marketing checkbox, and the
// "one number or symbol" rule (our API only requires 8 characters).
export function RegisterPage() {
  const { user, register } = useAuth()
  const [params] = useSearchParams()
  const { search } = useLocation()

  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [repeatPassword, setRepeatPassword] = useState('')
  const [showPassword, setShowPassword] = useState(false)
  const [errors, setErrors] = useState<FormErrors>(NO_ERRORS)
  const [isSubmitting, setIsSubmitting] = useState(false)

  if (user) return <Navigate to={safeNext(params.get('next'))} replace />

  /** Updates a field and removes its error message, so the error goes away as soon as the user fixes it. */
  function edit(field: string, setValue: (value: string) => void) {
    return (value: string) => {
      setValue(value)
      setErrors((current) => ({ ...clearField(current, field), message: null }))
    }
  }

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()

    // The only rule checked here: the API never sees the repeated password.
    if (password !== repeatPassword) {
      setErrors({ fields: { repeatPassword: 'Passwords don’t match.' }, message: null })
      return
    }

    setErrors(NO_ERRORS)
    setIsSubmitting(true)
    try {
      await register(email, password)
      // Success logs the user in, and the <Navigate> above takes over.
    } catch (err) {
      // 422: invalid email / short password, 409: email already registered. Both name the field.
      setErrors(toFormErrors(err))
      setIsSubmitting(false)
    }
  }

  const passwordType = showPassword ? 'text' : 'password'
  const isLongEnough = password.length >= MIN_PASSWORD_LENGTH

  return (
    <section className={styles.card}>
      <div>
        <h1 className={styles.title}>Create account</h1>
        <p className={styles.subtitle}>
          Already have an account? <Link to={`/login${search}`}>Login</Link>
        </p>
      </div>

      <form className={styles.form} onSubmit={handleSubmit} noValidate>
        <TextField
          id="email"
          label="Email"
          type="email"
          autoComplete="email"
          autoFocus
          value={email}
          onChange={edit('email', setEmail)}
          error={errors.fields.email}
        />
        <TextField
          id="password"
          label="Password"
          type={passwordType}
          autoComplete="new-password"
          value={password}
          onChange={edit('password', setPassword)}
          error={errors.fields.password}
        />
        <TextField
          id="repeatPassword"
          label="Repeat password"
          type={passwordType}
          autoComplete="new-password"
          value={repeatPassword}
          onChange={edit('repeatPassword', setRepeatPassword)}
          error={errors.fields.repeatPassword}
        />

        <button type="button" className={styles.toggle} onClick={() => setShowPassword((shown) => !shown)}>
          {showPassword ? 'Hide password' : 'Show password'}
        </button>

        <ul className={styles.rules}>
          <li className={`${styles.rule} ${isLongEnough ? styles.ruleMet : ''}`}>
            At least {MIN_PASSWORD_LENGTH} characters
            {isLongEnough && <span className="sr-only"> (done)</span>}
          </li>
        </ul>

        {errors.message && (
          <p className={styles.formError} role="alert">
            {errors.message}
          </p>
        )}

        <button type="submit" className={styles.submit} disabled={isSubmitting}>
          {isSubmitting ? 'Creating account…' : 'Create account'}
        </button>
      </form>
    </section>
  )
}
