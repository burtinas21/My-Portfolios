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

const MONTH_NAMES = Array.from({ length: 12 }, (_, index) => {
  const date = new Date(Date.UTC(2024, index, 1))
  return [
    new Intl.DateTimeFormat('en-US', { month: 'short', timeZone: 'UTC' })
      .format(date)
      .toLowerCase(),
    new Intl.DateTimeFormat('en-US', { month: 'long', timeZone: 'UTC' })
      .format(date)
      .toLowerCase(),
  ]
})

/**
 * Parse a month and year entered as "Apr 2024", "April 2024", "04/2024",
 * or "2024-04".
 *
 * @param {string} value
 * @returns {string|null} — Normalized YYYY-MM value
 */
export function parseMonthInput(value) {
  if (typeof value !== 'string' || !value.trim()) return null

  const input = value.trim()
  let year
  let month

  const isoMatch = input.match(/^(\d{4})-(\d{1,2})(?:-\d{1,2})?$/)
  const numericMatch = input.match(/^(\d{1,2})[/-](\d{4})$/)
  const namedMatch = input.match(/^([a-z]+)\.?\s+(\d{4})$/i)

  if (isoMatch) {
    year = Number(isoMatch[1])
    month = Number(isoMatch[2])
  } else if (numericMatch) {
    month = Number(numericMatch[1])
    year = Number(numericMatch[2])
  } else if (namedMatch) {
    year = Number(namedMatch[2])
    const monthName = namedMatch[1].toLowerCase()
    month = MONTH_NAMES.findIndex((names, index) => (
      names.includes(monthName) || (index === 8 && monthName === 'sept')
    )) + 1
  } else {
    return null
  }

  if (!Number.isInteger(year) || year < 1 || !Number.isInteger(month) || month < 1 || month > 12) {
    return null
  }

  return `${String(year).padStart(4, '0')}-${String(month).padStart(2, '0')}`
}

/**
 * Format a date or month value as a short month and year for text inputs.
 *
 * @param {string|null} value
 * @returns {string}
 */
export function formatMonthInput(value) {
  const month = parseMonthInput(value || '')
  if (!month) return ''

  const [year, monthNumber] = month.split('-').map(Number)
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    year: 'numeric',
    timeZone: 'UTC',
  }).format(new Date(Date.UTC(year, monthNumber - 1, 1)))
}

/**
 * Format a work experience date range and inclusive month duration.
 *
 * @param {string|null} start — ISO date for the first month
 * @param {string|null} end — ISO date for the final month
 * @param {boolean} isCurrent — Whether the role continues through this month
 * @returns {string} — e.g. "Apr 2026 – Present · 7 mo"
 */
export function formatPeriod(start, end, isCurrent = false) {
  if (!start) return ''

  const toMonthDate = (value) => {
    if (typeof value !== 'string' || !/^\d{4}-\d{2}/.test(value)) return null
    const [year, month] = value.slice(0, 7).split('-').map(Number)
    if (month < 1 || month > 12) return null
    return new Date(Date.UTC(year, month - 1, 1))
  }

  const startDate = toMonthDate(start)
  if (!startDate) return ''

  const endDate = isCurrent
    ? new Date(Date.UTC(new Date().getFullYear(), new Date().getMonth(), 1))
    : toMonthDate(end)
  const monthFormatter = new Intl.DateTimeFormat('en-US', {
    month: 'short',
    year: 'numeric',
    timeZone: 'UTC',
  })
  const startLabel = monthFormatter.format(startDate)

  if (!endDate) return startLabel

  const totalMonths = (endDate.getUTCFullYear() - startDate.getUTCFullYear()) * 12
    + endDate.getUTCMonth() - startDate.getUTCMonth() + 1
  if (totalMonths < 1) return startLabel

  const durationYears = Math.floor(totalMonths / 12)
  const durationMonths = totalMonths % 12
  const duration = [
    durationYears ? `${durationYears} yr` : '',
    durationMonths ? `${durationMonths} mo` : '',
  ].filter(Boolean).join(' ')
  const endLabel = isCurrent ? 'Present' : monthFormatter.format(endDate)

  return `${startLabel} – ${endLabel}${duration ? ` · ${duration}` : ''}`
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
