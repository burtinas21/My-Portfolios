/**
 * api/experiences.js — Public Experiences API
 * ────────────────────────────────────────────
 * Fetches work experiences for the timeline section.
 *
 * Response shape (from API.md):
 *   GET /experiences → [ { id, role, organization, period, description, sort_order, ... } ]
 *
 * Already sorted by sort_order on the backend.
 *
 * Used by: src/stores/portfolio.js
 */

import http from '@/api/http.js'

/**
 * Get all work experiences.
 * @returns {Promise<Array>} — Array of experience objects
 */
export function getExperiences() {
  return http.get('/experiences').then((res) => res.data)
}
