// Money comes from the API as integer EUR cents (PLAN.md §3). This turns 659 into "€6.59".
// en-IE: euro sign before the number and a dot for decimals, as on eneba.com's EU site.
const eur = new Intl.NumberFormat('en-IE', { style: 'currency', currency: 'EUR' })

export function formatPrice(cents: number): string {
  return eur.format(cents / 100)
}
