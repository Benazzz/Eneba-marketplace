import { useState, type FocusEvent, type FormEvent, type ReactNode } from 'react'
import type { FacetValue, ProductFilters } from '../api/types'
import type { StoreQuery } from '../utils/storeQuery'
import { ChevronDownIcon } from './icons'
import styles from './StoreFilters.module.css'

type Props = {
  query: StoreQuery
  /** Options and counts from GET /api/products/filters. Null until the first answer arrives. */
  filters: ProductFilters | null
  onTogglePlatform: (value: string) => void
  onToggleRegion: (value: string) => void
  onPriceChange: (min: number | null, max: number | null) => void
}

// The store's left-hand filter panel (02-store-filters-top.png): price range, platforms, regions.
// It only shows the URL state and reports clicks. StorePage turns them into URL changes.
export function StoreFilters({ query, filters, onTogglePlatform, onToggleRegion, onPriceChange }: Props) {
  return (
    <div className={styles.panel}>
      <Section title="Price range (EUR)" activeCount={query.minPrice !== null || query.maxPrice !== null ? 1 : 0}>
        <PriceRange
          min={query.minPrice}
          max={query.maxPrice}
          boundsCents={filters?.price ?? null}
          onChange={onPriceChange}
        />
      </Section>

      <Section title="Platforms" activeCount={query.platforms.length}>
        <CheckboxList
          name="platform"
          options={filters?.platforms ?? null}
          selected={query.platforms}
          onToggle={onTogglePlatform}
        />
      </Section>

      <Section title="Regions" activeCount={query.regions.length}>
        <CheckboxList
          name="region"
          options={filters?.regions ?? null}
          selected={query.regions}
          onToggle={onToggleRegion}
          capitalize
        />
      </Section>
    </div>
  )
}

/** A collapsible block. <details>/<summary> is the browser's own disclosure widget: no state, keyboard-ready. */
function Section({ title, activeCount, children }: { title: string; activeCount: number; children: ReactNode }) {
  return (
    <details className={styles.section} open>
      <summary className={styles.summary}>
        <span>{title}</span>
        {activeCount > 0 && (
          <span className={styles.badge} aria-label={`${activeCount} selected`}>
            {activeCount}
          </span>
        )}
        <ChevronDownIcon size={20} className={styles.chevron} />
      </summary>
      <div className={styles.content}>{children}</div>
    </details>
  )
}

type CheckboxListProps = {
  name: string
  options: FacetValue[] | null
  selected: string[]
  onToggle: (value: string) => void
  /** "NORTH AMERICA" → "North America" (display only). */
  capitalize?: boolean
}

function CheckboxList({ name, options, selected, onToggle, capitalize = false }: CheckboxListProps) {
  if (options === null) {
    return (
      <ul className={styles.list} aria-hidden="true">
        {[1, 2, 3].map((i) => (
          <li key={i} className={`${styles.skeletonRow} skeleton`} />
        ))}
      </ul>
    )
  }

  return (
    <ul className={styles.list}>
      {options.map((option) => {
        const checked = selected.includes(option.value)
        // A box that would give no results is disabled, unless it's ticked (it must stay untickable).
        const disabled = option.count === 0 && !checked
        return (
          <li key={option.value}>
            <label className={`${styles.option} ${disabled ? styles.optionDisabled : ''}`}>
              <input
                type="checkbox"
                name={name}
                className={styles.checkbox}
                checked={checked}
                disabled={disabled}
                onChange={() => onToggle(option.value)}
              />
              <span className={`${styles.optionLabel} ${capitalize ? styles.capitalize : ''}`}>
                {capitalize ? option.value.toLowerCase() : option.value}
              </span>
              <span className={styles.count}>{option.count}</span>
            </label>
          </li>
        )
      })}
    </ul>
  )
}

type PriceRangeProps = {
  /** Euros from the URL. */
  min: number | null
  max: number | null
  /** Cheapest and dearest "From" price for the current filters, used as placeholders. */
  boundsCents: ProductFilters['price'] | null
  onChange: (min: number | null, max: number | null) => void
}

function toInput(value: number | null): string {
  return value === null ? '' : String(value)
}

function parseInput(value: string): number | null {
  if (value.trim() === '') return null
  const n = Number(value.replace(',', '.'))
  return Number.isFinite(n) && n >= 0 ? n : null
}

/**
 * Two inputs that apply on Enter, or when focus leaves the pair (not on every key, which would
 * reload the grid mid-typing). They keep their own text while the user types, and take the URL's
 * values again whenever those change (e.g. "Clear all" or the back button).
 */
function PriceRange({ min, max, boundsCents, onChange }: PriceRangeProps) {
  const [minText, setMinText] = useState(toInput(min))
  const [maxText, setMaxText] = useState(toInput(max))

  // React's pattern for "reset state when a prop changes": compare with the last seen value during
  // render, instead of an effect (which would show the old text for one frame first).
  const [seen, setSeen] = useState({ min, max })
  if (seen.min !== min || seen.max !== max) {
    setSeen({ min, max })
    setMinText(toInput(min))
    setMaxText(toInput(max))
  }

  function apply() {
    let newMin = parseInput(minText)
    let newMax = parseInput(maxText)
    // Typed the wrong way round: swap instead of showing nothing.
    if (newMin !== null && newMax !== null && newMin > newMax) [newMin, newMax] = [newMax, newMin]
    if (newMin !== min || newMax !== max) onChange(newMin, newMax)
    else {
      // Nothing changed (e.g. invalid text): show the applied values again.
      setMinText(toInput(min))
      setMaxText(toInput(max))
    }
  }

  function handleSubmit(e: FormEvent) {
    e.preventDefault()
    apply()
  }

  function handleBlur(e: FocusEvent<HTMLFormElement>) {
    // relatedTarget is where focus goes next. Moving between the two inputs doesn't count as leaving.
    if (!e.currentTarget.contains(e.relatedTarget)) apply()
  }

  const minPlaceholder = boundsCents?.minCents != null ? String(Math.floor(boundsCents.minCents / 100)) : 'Min'
  const maxPlaceholder = boundsCents?.maxCents != null ? String(Math.ceil(boundsCents.maxCents / 100)) : 'Max'

  return (
    <form className={styles.priceForm} onSubmit={handleSubmit} onBlur={handleBlur}>
      <input
        type="text"
        inputMode="decimal"
        className={styles.priceInput}
        aria-label="Minimum price in euros"
        placeholder={minPlaceholder}
        value={minText}
        onChange={(e) => setMinText(e.target.value)}
      />
      <span aria-hidden="true">-</span>
      <input
        type="text"
        inputMode="decimal"
        className={styles.priceInput}
        aria-label="Maximum price in euros"
        placeholder={maxPlaceholder}
        value={maxText}
        onChange={(e) => setMaxText(e.target.value)}
      />
      {/* A form with two text inputs only submits on Enter if it has a submit button. */}
      <button type="submit" className="sr-only">
        Apply price range
      </button>
    </form>
  )
}
