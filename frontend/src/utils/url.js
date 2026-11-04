/**
 * url.js — Asset URL helper
 * ──────────────────────────
 * Single source of truth for turning a stored image path into a displayable URL.
 *
 * The backend stores image paths as relative strings like "/storage/uploads/photo.png".
 * To display them we prepend VITE_BACKEND_URL (set in .env / deployment config).
 *
 * Rule: if the value already starts with "http" it is returned unchanged (external images,
 * or legacy full URLs that haven't been migrated yet with `php artisan images:make-relative`).
 *
 * Usage:
 *   import { assetUrl } from '@/utils/url.js'
 *   <img :src="assetUrl(profile.avatar)" />
 */

/** Backend root — set VITE_BACKEND_URL in your .env / deployment environment. */
const BACKEND_URL = import.meta.env.VITE_BACKEND_URL || 'http://127.0.0.1:8000'

/**
 * Convert a stored image path into a displayable URL.
 *
 * @param {string|null} value — relative path ("/storage/...") or any string from the API
 * @returns {string|null} — full URL ready for <img src>, or null when value is empty
 *
 * @example
 *   assetUrl('/storage/uploads/photo.png')
 *   // → "http://127.0.0.1:8000/storage/uploads/photo.png"
 *
 *   assetUrl('https://cdn.example.com/img.jpg')
 *   // → "https://cdn.example.com/img.jpg"  (returned unchanged)
 *
 *   assetUrl(null) // → null
 */
export function assetUrl(value) {
  if (!value) return null

  // Already an absolute URL — return as-is (external images or legacy full backend URLs)
  if (value.startsWith('http://') || value.startsWith('https://')) {
    return value
  }

  // Relative path — prepend the backend base URL.
  // Ensure exactly one slash between the base and the path.
  const base = BACKEND_URL.replace(/\/+$/, '')
  const cleanPath = value.startsWith('/') ? value : `/${value}`
  return `${base}${cleanPath}`
}

// Keep the legacy export name so any import that wasn't updated yet still works.
// Once all call-sites use assetUrl(), this alias can be removed.
export { assetUrl as imageUrl }
