/**
 * api/admin/messages.js — Admin Messages API
 * ────────────────────────────────────────────
 * Manage contact form messages (read-only list, mark as read, delete).
 *
 * GET    /admin/messages          → all messages (latest first)
 * PATCH  /admin/messages/:id/read → mark message as read
 * DELETE /admin/messages/:id      → delete message
 *
 * Used by: views/admin/MessagesView.vue
 */

import http from '@/api/http.js'

export function getAdminMessages() {
  return http.get('/admin/messages').then((res) => res.data)
}

export function markMessageRead(id) {
  return http.patch(`/admin/messages/${id}/read`).then((res) => res.data)
}

export function deleteAdminMessage(id) {
  return http.delete(`/admin/messages/${id}`)
}
