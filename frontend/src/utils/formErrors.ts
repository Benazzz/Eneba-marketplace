import { ApiError } from '../api/client'

export type FormErrors = {
  /** Field name → message, shown under that field. */
  fields: Record<string, string>
  /** One message for the whole form (wrong login, network error), or null. */
  message: string | null
}

export const NO_ERRORS: FormErrors = { fields: {}, message: null }

/**
 * Turns a failed form submit into what the form shows. Errors that name fields (422 validation_failed,
 * 409 email_taken) go under those fields. Anything else becomes one message above the button.
 */
export function toFormErrors(err: unknown): FormErrors {
  if (err instanceof ApiError) {
    if (err.fields && Object.keys(err.fields).length > 0) return { fields: err.fields, message: null }
    return { fields: {}, message: err.message }
  }
  return { fields: {}, message: 'Something went wrong. Please try again.' }
}

/** The same errors without this field's message: used when the user edits the field. */
export function clearField(errors: FormErrors, field: string): FormErrors {
  if (!(field in errors.fields)) return errors
  const { [field]: _removed, ...rest } = errors.fields
  return { ...errors, fields: rest }
}
