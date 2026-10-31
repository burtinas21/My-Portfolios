/**
 * api/admin/skills.js — Admin Skills API
 * ────────────────────────────────────────
 * Full CRUD for skills.
 *
 * GET    /admin/skills     → all skills (flat array, not grouped)
 * POST   /admin/skills     → create skill
 * PUT    /admin/skills/:id → update skill
 * DELETE /admin/skills/:id → delete skill
 *
 * Used by: views/admin/SkillsView.vue
 */

import http from '@/api/http.js'

export function getAdminSkills() {
  return http.get('/admin/skills').then((res) => res.data)
}

export function createAdminSkill(data) {
  return http.post('/admin/skills', data).then((res) => res.data)
}

export function updateAdminSkill(id, data) {
  return http.put(`/admin/skills/${id}`, data).then((res) => res.data)
}

export function deleteAdminSkill(id) {
  return http.delete(`/admin/skills/${id}`)
}
