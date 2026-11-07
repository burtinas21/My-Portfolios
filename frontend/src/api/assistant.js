/**
 * api/assistant.js — AI Assistant API
 * ────────────────────────────────────
 * Public API client for the portfolio AI assistant.
 *
 * Endpoints:
 *   GET /assistant — Fetch greeting, suggested questions, enabled status
 *   POST /chat — Send messages and receive AI replies
 *
 * Used by: src/stores/chat.js
 */

import http from '@/api/http.js'

/**
 * Get assistant configuration (greeting, suggested questions, enabled status).
 * @returns {Promise<object>} — { enabled, greeting, suggested_questions }
 */
export function getAssistant() {
  return http.get('/assistant').then((res) => res.data)
}

/**
 * Send a message to the assistant and get a reply.
 *
 * @param {Array} messages — Array of { role: "user"|"assistant", content: string }
 * @returns {Promise<object>} — { reply: string }
 */
export function sendChat(messages) {
  return http.post('/chat', { messages }).then((res) => res.data)
}
