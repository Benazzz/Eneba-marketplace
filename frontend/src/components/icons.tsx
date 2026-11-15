// Small inline SVG icons (no icon library). They draw with currentColor, so they take the text color of their parent.
// aria-hidden: they are decorative. The link or button around them carries the accessible name.

import type { ReactNode } from 'react'

type IconProps = {
  size?: number
  className?: string
}

function Svg({ size = 24, className, children }: IconProps & { children: ReactNode }) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth={1.6}
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      className={className}
    >
      {children}
    </svg>
  )
}

export function SearchIcon(props: IconProps) {
  return (
    <Svg {...props}>
      <circle cx="10.5" cy="10.5" r="7" />
      <path d="M15.5 15.5 21 21" />
    </Svg>
  )
}

export function CloseIcon(props: IconProps) {
  return (
    <Svg {...props}>
      <path d="M6 6l12 12M18 6 6 18" />
    </Svg>
  )
}

export function CartIcon(props: IconProps) {
  return (
    <Svg {...props}>
      <path d="M2.5 4h2.2l2.4 10.3a1 1 0 0 0 1 .7h9.3a1 1 0 0 0 1-.7L20.6 8H6" />
      <circle cx="9" cy="19.5" r="1.3" />
      <circle cx="17" cy="19.5" r="1.3" />
    </Svg>
  )
}

/** Cart with a plus, for "add to cart" buttons. */
export function CartPlusIcon(props: IconProps) {
  return (
    <Svg {...props}>
      <path d="M2.5 4h2.2l2.4 10.3a1 1 0 0 0 1 .7h9.3a1 1 0 0 0 1-.7L20.6 8H17" />
      <path d="M11.5 5v6M8.5 8h6" />
      <circle cx="9" cy="19.5" r="1.3" />
      <circle cx="17" cy="19.5" r="1.3" />
    </Svg>
  )
}

export function HomeIcon(props: IconProps) {
  return (
    <Svg {...props}>
      <path d="M3.5 11 12 4l8.5 7M6 9.5V20h4.5v-5.5h3V20H18V9.5" />
    </Svg>
  )
}

/** Filled badge with a tick, next to verified sellers. */
export function VerifiedIcon(props: IconProps) {
  return (
    <Svg {...props}>
      <path
        d="M12 2.5l2.4 1.7 2.9-.2.9 2.8 2.4 1.7-.9 2.8.9 2.8-2.4 1.7-.9 2.8-2.9-.2L12 21.5l-2.4-1.7-2.9.2-.9-2.8-2.4-1.7.9-2.8-.9-2.8 2.4-1.7.9-2.8 2.9.2z"
        fill="currentColor"
        stroke="none"
      />
      <path d="M8.5 12.2l2.3 2.3 4.7-4.8" stroke="var(--color-bg-deep)" strokeWidth={2} />
    </Svg>
  )
}

export function StarIcon(props: IconProps) {
  return (
    <Svg {...props}>
      <path
        d="M12 3.2l2.7 5.6 6.1.8-4.5 4.2 1.1 6.1L12 17l-5.4 2.9 1.1-6.1-4.5-4.2 6.1-.8z"
        fill="currentColor"
        stroke="none"
      />
    </Svg>
  )
}

export function UserIcon(props: IconProps) {
  return (
    <Svg {...props}>
      <circle cx="12" cy="7.5" r="4" />
      <path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8" />
    </Svg>
  )
}
