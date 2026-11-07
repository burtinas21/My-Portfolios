/**
 * api/admin/certifications.js — Admin Certifications API
 * ──────────────────────────────────────────────────────
 * Full CRUD and ordering for certifications, including drafts.
 */

import http from '@/api/http.js'

export function getAdminCertifications() {
  return http.get('/admin/certifications').then((res) => res.data)
}

export function createAdminCertification(data) {
  return http.post('/admin/certifications', data).then((res) => res.data)
}

export function updateAdminCertification(id, data) {
  return http.put(`/admin/certifications/${id}`, data).then((res) => res.data)
}

export function deleteAdminCertification(id) {
  return http.delete(`/admin/certifications/${id}`)
}

export function reorderAdminCertifications(certifications) {
  return http.patch('/admin/certifications/reorder', { certifications }).then((res) => res.data)
}