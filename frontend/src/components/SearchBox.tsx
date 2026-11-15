import { useEffect, useId, useRef, useState, type KeyboardEvent, type RefObject } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import type { SearchSuggestResponse } from '../api/types'
import { useDebounce } from '../hooks/useDebounce'
import { useFetch } from '../hooks/useFetch'
import { formatPrice } from '../utils/formatPrice'
import { CloseIcon, SearchIcon } from './icons'
import styles from './SearchBox.module.css'

const MIN_LENGTH = 2 // the API returns nothing for shorter queries
const DEBOUNCE_MS = 250

type Props = {
  className?: string
  /** Lets the header focus the input (the mobile search icon opens the box). */
  inputRef?: RefObject<HTMLInputElement | null>
  /** Called after the user picks a result or presses Esc on a closed dropdown. The header uses it to hide mobile search. */
  onDone?: () => void
}

// Header search with a live results dropdown (PLAN.md §5).
// Follows the ARIA combobox pattern: focus stays in the input, and ↑/↓ move a highlight through the
// options (aria-activedescendant), so screen readers announce the highlighted option.
export function SearchBox({ className, inputRef, onDone }: Props) {
  const navigate = useNavigate()
  const ownRef = useRef<HTMLInputElement>(null)
  const ref = inputRef ?? ownRef
  const listId = useId()

  const [query, setQuery] = useState('')
  const [isOpen, setIsOpen] = useState(false)
  /** Highlighted option: 0…items.length-1 are products, items.length is "See all results". -1 = none. */
  const [activeIndex, setActiveIndex] = useState(-1)

  const term = query.trim()
  const debounced = useDebounce(term, DEBOUNCE_MS)
  // useFetch cancels the previous request (AbortController) whenever the path changes, so a slow answer
  // for "cyb" can never replace the answer for "cyberpunk".
  const path = debounced.length >= MIN_LENGTH ? `/api/search/suggest?q=${encodeURIComponent(debounced)}` : null
  const { data, error, isLoading } = useFetch<SearchSuggestResponse>(path, { keepPrevious: true })

  const items = data?.items ?? []
  /** Waiting for the debounce or the request. While waiting, the previous results stay visible (dimmed). */
  const isPending = isLoading || term !== debounced
  const showDropdown = isOpen && term.length >= MIN_LENGTH
  const optionCount = items.length > 0 ? items.length + 1 : 0
  // New results may be shorter than the old highlight. Then nothing is highlighted.
  const active = activeIndex < optionCount ? activeIndex : -1
  const storeUrl = `/store?q=${encodeURIComponent(term)}`
  const optionId = (index: number) => `${listId}-option-${index}`

  // Keep the highlighted option visible when the list scrolls.
  useEffect(() => {
    if (active >= 0) document.getElementById(`${listId}-option-${active}`)?.scrollIntoView({ block: 'nearest' })
  }, [active, listId])

  function close() {
    setIsOpen(false)
    setActiveIndex(-1)
    ref.current?.blur()
    onDone?.()
  }

  function closeAfterProductPick() {
    setQuery('')
    close()
  }

  function openProduct(slug: string) {
    closeAfterProductPick()
    navigate(`/product/${slug}`)
  }

  function openStore() {
    if (term === '') return
    close()
    navigate(storeUrl)
  }

  function handleKeyDown(e: KeyboardEvent<HTMLInputElement>) {
    switch (e.key) {
      case 'ArrowDown':
      case 'ArrowUp': {
        e.preventDefault() // otherwise the cursor jumps to the start/end of the text
        if (!showDropdown) {
          setIsOpen(true)
          return
        }
        if (optionCount === 0) return
        const step = e.key === 'ArrowDown' ? 1 : -1
        // Wraps around: ↓ on the last option goes to the first, ↑ on the first (or none) goes to the last.
        setActiveIndex(active === -1 && step === -1 ? optionCount - 1 : (active + step + optionCount) % optionCount)
        return
      }
      case 'Escape':
        e.preventDefault() // a type="search" input would also clear its text
        if (showDropdown) {
          setIsOpen(false)
          setActiveIndex(-1)
        } else {
          close()
        }
        return
      // Enter is handled by the form's onSubmit.
    }
  }

  return (
    <div className={`${styles.root} ${className ?? ''}`}>
      <form
        role="search"
        className={`${styles.box} ${showDropdown ? styles.boxOpen : ''}`}
        onSubmit={(e) => {
          e.preventDefault()
          if (showDropdown && active >= 0 && active < items.length) openProduct(items[active].slug)
          else openStore()
        }}
      >
        <SearchIcon size={26} className={styles.icon} />
        <input
          ref={ref}
          type="search"
          className={styles.input}
          placeholder="Search for games, top-ups and more"
          aria-label="Search for games, top-ups and more"
          autoComplete="off"
          role="combobox"
          aria-autocomplete="list"
          aria-expanded={showDropdown}
          aria-controls={listId}
          aria-activedescendant={showDropdown && active >= 0 ? optionId(active) : undefined}
          value={query}
          onChange={(e) => {
            setQuery(e.target.value)
            setIsOpen(true)
            setActiveIndex(-1)
          }}
          onFocus={() => setIsOpen(true)}
          onBlur={() => setIsOpen(false)}
          onKeyDown={handleKeyDown}
        />
        {query !== '' && (
          <button
            type="button"
            className={styles.clear}
            aria-label="Clear search"
            // preventDefault on mousedown keeps focus in the input, so the dropdown doesn't close and reopen.
            onMouseDown={(e) => e.preventDefault()}
            onClick={() => {
              setQuery('')
              setActiveIndex(-1)
              ref.current?.focus()
            }}
          >
            <CloseIcon size={22} />
          </button>
        )}
      </form>

      {showDropdown && (
        // preventDefault on mousedown: clicking a result must not blur the input first,
        // or the dropdown would close before the click lands.
        <div className={styles.dropdown} onMouseDown={(e) => e.preventDefault()}>
          {items.length === 0 && (
            <p role="status" className={styles.message}>
              {error
                ? 'Search is unavailable right now. Please try again.'
                : isPending
                  ? 'Searching…'
                  : `No results for "${debounced}"`}
            </p>
          )}

          {items.length > 0 && (
            <ul
              id={listId}
              role="listbox"
              aria-label="Search results"
              className={`${styles.list} ${isPending ? styles.stale : ''}`}
            >
              {items.map((product, index) => (
                <li
                  key={product.id}
                  id={optionId(index)}
                  role="option"
                  aria-selected={index === active}
                  className={`${styles.option} ${index === active ? styles.active : ''}`}
                  onMouseEnter={() => setActiveIndex(index)}
                >
                  {/* A real link, so middle-click and ctrl+click open a new tab. The Link navigates by itself,
                      onClick only tidies up. tabIndex -1: keyboard focus stays in the input. */}
                  <Link
                    to={`/product/${product.slug}`}
                    tabIndex={-1}
                    className={styles.result}
                    onClick={closeAfterProductPick}
                  >
                    <img src={product.coverUrl} alt="" loading="lazy" className={styles.cover} />
                    <span className={styles.info}>
                      <span className={styles.name}>{product.name}</span>
                      <span className={styles.meta}>
                        {product.platform} · {product.region}
                      </span>
                    </span>
                    <span className={styles.price}>
                      {product.fromPriceCents === null ? (
                        <span className={styles.soldOut}>Out of stock</span>
                      ) : (
                        <>
                          <span className={styles.from}>From</span>
                          <span className={styles.amount}>{formatPrice(product.fromPriceCents)}</span>
                        </>
                      )}
                    </span>
                  </Link>
                </li>
              ))}

              <li
                id={optionId(items.length)}
                role="option"
                aria-selected={active === items.length}
                className={`${styles.option} ${active === items.length ? styles.active : ''}`}
                onMouseEnter={() => setActiveIndex(items.length)}
              >
                <Link to={storeUrl} tabIndex={-1} className={styles.seeAll} onClick={close}>
                  See all results for "{term}"
                </Link>
              </li>
            </ul>
          )}
        </div>
      )}
    </div>
  )
}
