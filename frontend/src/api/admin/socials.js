/**
 * api/admin/socials.js — Admin Social Links API
 * ─────────────────────────────────────────────
 * Full CRUD for social links.
 *
 * GET    /admin/socials     → all social links
 * POST   /admin/socials     → create social link
 * PUT    /admin/socials/:id → update social link
 * DELETE /admin/socials/:id → delete social link
 *
 * Used by: views/admin/SocialsView.vue
 */

import http from '@/api/http.js'

export function getAdminSocials() {
  return http.get('/admin/socials').then((res) => res.data)
}

export function createAdminSocial(data) {
  return http.post('/admin/socials', data).then((res) => res.data)
}

export function updateAdminSocial(id, data) {
  return http.put(`/admin/socials/${id}`, data).then((res) => res.data)
}

export function deleteAdminSocial(id) {
  return http.delete(`/admin/socials/${id}`)
}
