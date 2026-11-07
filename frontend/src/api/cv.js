/**
 * api/cv.js — Public CV API
 * ──────────────────────────
 * GET /cv → get CV availability info (never exposes path or internal details)
 *
 * Used by: CvDownloadButton.vue, CvCard.vue
 */

import http from '@/api/http.js'

/**
 * Get CV availability information.
 * @returns {Promise<object>} — { available: boolean, size: number|null, updated_at: string|null }
 */
export function getCvInfo() {
  return http.get('/cv').then((res) => res.data)
}

/**
 * Build the CV download URL.
 * @param {boolean} inline - Whether to view inline (true) or download (false)
 * @returns {string} — Full download URL
 */
export function getCvDownloadUrl(inline = false) {
  const baseUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'
  const url = `${baseUrl}/cv/download`
  return inline ? `${url}?inline=1` : url
}
