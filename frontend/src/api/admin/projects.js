/**
 * api/admin/projects.js — Admin Projects API
 * ────────────────────────────────────────────
 * Full CRUD for projects (includes unpublished drafts).
 *
 * GET    /admin/projects     → all projects (including unpublished)
 * POST   /admin/projects     → create project
 * PUT    /admin/projects/:id → update project
 * DELETE /admin/projects/:id → delete project (also removes image)
 *
 * Used by: views/admin/ProjectsView.vue
 */

import http from '@/api/http.js'

export function getAdminProjects() {
  return http.get('/admin/projects').then((res) => res.data)
}

export function createAdminProject(data) {
  return http.post('/admin/projects', data).then((res) => res.data)
}

export function updateAdminProject(id, data) {
  return http.put(`/admin/projects/${id}`, data).then((res) => res.data)
}

export function deleteAdminProject(id) {
  return http.delete(`/admin/projects/${id}`)
}
