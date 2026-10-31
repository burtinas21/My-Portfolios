/**
 * api/messages.js — Public Contact Messages API
 * ──────────────────────────────────────────────
 * Sends a contact form message.
 *
 * Request:  POST /messages { name, email, body }
 * Response: 201 { message: "Thank you! I will reply soon." }
 * Errors:   422 validation, 429 rate limit (5 per minute)
 *
 * Used by: ContactSection.vue (via the portfolio store or directly)
 */

import http from '@/api/http.js'

/**
 * Send a contact message.
 * @param {{ name: string, email: string, body: string }} data
 * @returns {Promise<{ message: string }>}
 */
export function sendMessage(data) {
  return http.post('/messages', data).then((res) => res.data)
}
