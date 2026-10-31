/**
 * api/projects.js — Public Projects API
 * ──────────────────────────────────────
 * Fetches published projects.
 *
 * Response shapes (from API.md):
 *   GET /projects     → [ { id, title, summary, detail, tag, year, url, image, stack[], is_published, sort_order, ... } ]
 *   GET /projects/:id → { id, title, summary, detail, tag, year, url, image, stack[], is_published, sort_order, ... }
 *
 * Note: image is a RELATIVE path like "/storage/uploads/project1.png".
 * Note: stack is a JSON array of strings like ["Laravel", "Vue.js"].
 * Note: GET /projects only returns published projects (is_published: true).
 * Note: GET /projects/:id returns 404 for unpublished or missing projects.
 *
 * Used by: src/stores/portfolio.js
 */

import http from '@/api/http.js'

/**
 * Get all published projects.
 * @returns {Promise<Array>} — Array of published project objects
 */
export function getProjects() {
  return http.get('/projects').then((res) => res.data)
}

/**
 * Get a single published project by ID.
 * @param {number|string} id — The project ID
 * @returns {Promise<object>} — The project object
 */
export function getProject(id) {
  return http.get(`/projects/${id}`).then((res) => res.data)
}
