import { useState, type FormEvent } from 'react'
import { Link, Navigate, useLocation, useSearchParams } from 'react-router-dom'
import { TextField } from '../components/TextField'
import { useAuth } from '../hooks/useAuth'
import { clearField, NO_ERRORS, toFormErrors, type FormErrors } from '../utils/formErrors'
import { safeNext } from '../utils/safeNext'
import styles from './AuthPage.module.css'

// docs/design-reference/10-login.png, with email + password only (social logins and the magic link are cut).
export function LoginPage() {
  const { user, login } = useAuth()
  const [params] = useSearchParams()
  // Keeps ?next=… on the "Create account" link, so registering also returns the user to where they were.
  const { search } = useLocation()

  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [errors, setErrors] = useState<FormErrors>(NO_ERRORS)
  const [isSubmitting, setIsSubmitting] = useState(false)

  // Logged in (already, or just now after submitting): continue to ?next=, or the homepage.
  if (user) return <Navigate to={safeNext(params.get('next'))} replace />

  async function handleSubmit(e: FormEvent) {
    e.preventDefault() // stop the browser's own page-reloading form submit
    setErrors(NO_ERRORS)
    setIsSubmitting(true)
    try {
      await login(email, password)
      // Success sets `user`, and the <Navigate> above takes over on the next render.
    } catch (err) {
      // A wrong email and a wrong password get the same message from the API ("Wrong email or password."),
      // so the page never reveals whether an email is registered.
      setErrors(toFormErrors(err))
      setIsSubmitting(false)
    }
  }

  return (
    <section className={styles.card}>
      <div>
        <h1 className={styles.title}>Log in</h1>
        <p className={styles.subtitle}>
          New user? <Link to={`/register${search}`}>Create account</Link>
        </p>
      </div>

      {/* noValidate: the API checks the fields, and its messages are shown in our own style. */}
      <form className={styles.form} onSubmit={handleSubmit} noValidate>
        <TextField
          id="email"
          label="Email"
          type="email"
          autoComplete="email"
          autoFocus
          value={email}
          onChange={(value) => {
            setEmail(value)
            setErrors((current) => ({ ...clearField(current, 'email'), message: null }))
          }}
          error={errors.fields.email}
        />
        <TextField
          id="password"
          label="Password"
          type="password"
          autoComplete="current-password"
          value={password}
          onChange={(value) => {
            setPassword(value)
            setErrors((current) => ({ ...clearField(current, 'password'), message: null }))
          }}
          error={errors.fields.password}
        />

        {errors.message && (
          <p className={styles.formError} role="alert">
            {errors.message}
          </p>
        )}

        <button type="submit" className={styles.submit} disabled={isSubmitting}>
          {isSubmitting ? 'Logging in…' : 'Log in'}
        </button>
      </form>
    </section>
  )
}
