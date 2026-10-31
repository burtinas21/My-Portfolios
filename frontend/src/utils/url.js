/**
 * url.js — URL helpers
 * ────────────────────
 * Turns relative image paths returned by the API (e.g. "/storage/uploads/photo.png")
 * into absolute URLs (e.g. "http://127.0.0.1:8000/storage/uploads/photo.png").
 *
 * Used by: any component that displays images (projects, profile avatar).
 */

/**
 * The backend root URL, read from the .env file.
 * Vite replaces this at build time so the value is always a string.
 */
const BACKEND_URL = import.meta.env.VITE_BACKEND_URL || 'http://127.0.0.1:8000'

/**
 * Convert a relative image path from the API into a full URL.
 *
 * The API returns image paths like "/storage/uploads/1234_photo.png".
 * To show them in an <img> tag we need the complete URL including the host.
 *
 * @param {string|null} path — The relative path from the API (e.g. "/storage/uploads/img.png")
 * @returns {string|null} — The full URL, or null if no path was given
 *
 * @example
 *   imageUrl('/storage/uploads/photo.png')
 *   // → "http://127.0.0.1:8000/storage/uploads/photo.png"
 *
 *   imageUrl(null) // → null
 *   imageUrl('https://example.com/img.jpg') // → "https://example.com/img.jpg" (unchanged)
 */
export function imageUrl(path) {
  if (!path) return null

  // If the path is already a full URL, return it as-is
  if (path.startsWith('http://') || path.startsWith('https://')) {
    return path
  }

  // Otherwise prepend the backend URL
  // Make sure there's exactly one slash between the base and the path
  const base = BACKEND_URL.replace(/\/+$/, '')
  const cleanPath = path.startsWith('/') ? path : `/${path}`
  return `${base}${cleanPath}`
}
