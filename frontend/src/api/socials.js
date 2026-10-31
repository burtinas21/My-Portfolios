/**
 * api/socials.js — Public Social Links API
 * ─────────────────────────────────────────
 * Fetches the portfolio owner's social media links.
 *
 * Response shape (from API.md):
 *   GET /socials → [ { id, name, url, sort_order, created_at, updated_at } ]
 *
 * The array is already sorted by sort_order on the backend.
 *
 * Used by: src/stores/portfolio.js
 */

import http from '@/api/http.js'

/**
 * Get all public social links.
 * @returns {Promise<Array>} — Array of social link objects
 */
export function getSocials() {
  return http.get('/socials').then((res) => res.data)
}
