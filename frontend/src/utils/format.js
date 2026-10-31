/**
 * format.js — Formatting helpers
 * ───────────────────────────────
 * Pure functions that format data for display.
 * These contain no side effects and no API calls.
 *
 * Used by: components that show dates, text, or other formatted values.
 */

/**
 * Format a date string into a human-readable form.
 *
 * @param {string} dateString — An ISO 8601 date (e.g. "2024-01-15T10:30:00.000000Z")
 * @param {object} [options]  — Intl.DateTimeFormat options
 * @returns {string} — Formatted date (e.g. "Jan 15, 2024")
 *
 * @example
 *   formatDate('2024-01-15T10:30:00.000000Z')
 *   // → "Jan 15, 2024"
 */
export function formatDate(dateString, options = {}) {
  if (!dateString) return ''
  const defaults = { month: 'short', day: 'numeric', year: 'numeric' }
  return new Date(dateString).toLocaleDateString('en-US', { ...defaults, ...options })
}

/**
 * Format a date string into a relative time description.
 *
 * @param {string} dateString — An ISO 8601 date
 * @returns {string} — e.g. "2 hours ago", "3 days ago", "just now"
 */
export function timeAgo(dateString) {
  if (!dateString) return ''

  const now = Date.now()
  const then = new Date(dateString).getTime()
  const seconds = Math.floor((now - then) / 1000)

  // Define time intervals in seconds
  const intervals = [
    { label: 'year', seconds: 31536000 },
    { label: 'month', seconds: 2592000 },
    { label: 'week', seconds: 604800 },
    { label: 'day', seconds: 86400 },
    { label: 'hour', seconds: 3600 },
    { label: 'minute', seconds: 60 },
  ]

  for (const interval of intervals) {
    const count = Math.floor(seconds / interval.seconds)
    if (count >= 1) {
      return `${count} ${interval.label}${count > 1 ? 's' : ''} ago`
    }
  }

  return 'just now'
}

/**
 * Truncate a string to a maximum length, adding "…" if it was cut.
 *
 * @param {string} text — The original text
 * @param {number} [maxLength=100] — Maximum characters before truncating
 * @returns {string}
 *
 * @example
 *   truncate('Hello world this is a long text', 15)
 *   // → "Hello world thi…"
 */
export function truncate(text, maxLength = 100) {
  if (!text || text.length <= maxLength) return text || ''
  return text.slice(0, maxLength).trimEnd() + '…'
}

/**
 * Capitalize the first letter of a string.
 *
 * @param {string} text
 * @returns {string}
 */
export function capitalize(text) {
  if (!text) return ''
  return text.charAt(0).toUpperCase() + text.slice(1)
}

/**
 * Generate a gradient background style string from the first letter
 * of a text — used as a placeholder when a project has no image.
 *
 * @param {string} text — Text to generate the gradient from (usually the project title)
 * @returns {{ background: string, initial: string }}
 */
export function placeholderGradient(text) {
  if (!text) return { background: 'linear-gradient(135deg, #5B3FFF, #8B7BFF)', initial: '?' }

  // Use the char code to pick a hue so each title gets a consistent color
  const charCode = text.charCodeAt(0)
  const hue = (charCode * 37) % 360

  return {
    background: `linear-gradient(135deg, hsl(${hue}, 65%, 55%), hsl(${(hue + 40) % 360}, 65%, 65%))`,
    initial: text.charAt(0).toUpperCase(),
  }
}
