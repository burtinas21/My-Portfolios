/**
 * api/admin/experiences.js — Admin Experiences API
 * ─────────────────────────────────────────────────
 * Full CRUD for work experiences.
 *
 * GET    /admin/experiences     → all experiences
 * POST   /admin/experiences     → create experience
 * PUT    /admin/experiences/:id → update experience
 * DELETE /admin/experiences/:id → delete experience
 *
 * Used by: views/admin/ExperienceView.vue
 */

import http from '@/api/http.js'

export function getAdminExperiences() {
  return http.get('/admin/experiences').then((res) => res.data)
}

export function createAdminExperience(data) {
  return http.post('/admin/experiences', data).then((res) => res.data)
}

export function updateAdminExperience(id, data) {
  return http.put(`/admin/experiences/${id}`, data).then((res) => res.data)
}

export function deleteAdminExperience(id) {
  return http.delete(`/admin/experiences/${id}`)
}
