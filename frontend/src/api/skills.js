/**
 * api/skills.js — Public Skills API
 * ──────────────────────────────────
 * Fetches skills grouped by category.
 *
 * Response shape (from API.md):
 *   GET /skills → { "Backend": [ { id, category, name, level, sort_order, ... } ], "Frontend": [...] }
 *
 * The response is an OBJECT where keys are category names and values are arrays of skills.
 * Each skill has a level from 0–100 (used for progress bars).
 *
 * Used by: src/stores/portfolio.js
 */

import http from '@/api/http.js'

/**
 * Get all skills grouped by category.
 * @returns {Promise<object>} — Object with category names as keys, skill arrays as values
 */
export function getSkills() {
  return http.get('/skills').then((res) => res.data)
}
