/**
 * api/admin/profile.js — Admin Profile API
 * ─────────────────────────────────────────
 * GET  /admin/profile  → fetch profile (admin version, same shape as public)
 * PUT  /admin/profile  → update profile fields
 *
 * Used by: views/admin/ProfileView.vue
 */

import http from '@/api/http.js'

/**
 * Get the admin profile.
 * @returns {Promise<object>} — Profile object
 */
export function getAdminProfile() {
  return http.get('/admin/profile').then((res) => res.data)
}

/**
 * Update the admin profile.
 * @param {object} data — { name, initials, headline, bio, email, location, status, avatar }
 * @returns {Promise<object>} — Updated profile object
 */
export function updateAdminProfile(data) {
  return http.put('/admin/profile', data).then((res) => res.data)
}
