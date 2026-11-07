/**
 * api/admin/assistant.js — Admin Assistant API
 * ─────────────────────────────────────────────
 * GET  /admin/assistant        → fetch settings
 * PUT  /admin/assistant        → update settings
 * GET  /admin/assistant/usage  → fetch token usage stats
 *
 * Used by: views/admin/AssistantView.vue
 */

import http from '@/api/http.js'

/**
 * Get the assistant settings.
 * @returns {Promise<object>} — { id, enabled, greeting, extra_knowledge, suggested_questions, ... }
 */
export function getAdminAssistant() {
  return http.get('/admin/assistant').then((res) => res.data)
}

/**
 * Update the assistant settings.
 * @param {object} data — { enabled, greeting, extra_knowledge, suggested_questions }
 * @returns {Promise<object>} — Updated settings object
 */
export function updateAdminAssistant(data) {
  return http.put('/admin/assistant', data).then((res) => res.data)
}

/**
 * Get assistant usage statistics.
 * @returns {Promise<object>} — { today: {requests, input_tokens, output_tokens}, last_7_days: {...}, last_30_days: {...} }
 */
export function getAdminAssistantUsage() {
  return http.get('/admin/assistant/usage').then((res) => res.data)
}
