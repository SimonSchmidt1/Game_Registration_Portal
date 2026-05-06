/**
 * Slovak grammatical number for "zobrazenie":
 *   0        → "0 zobrazení"
 *   1        → "1 zobrazenie"
 *   2–4      → "2 zobrazenia"
 *   5+       → "5 zobrazení"
 */
export function formatViews(count) {
  const n = parseInt(count, 10) || 0
  if (n === 1) return `${n} zobrazenie`
  if (n >= 2 && n <= 4) return `${n} zobrazenia`
  return `${n} zobrazení`
}
