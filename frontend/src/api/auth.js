/**
 * api/auth.js — Authentication API calls
 * ───────────────────────────────────────
 * Handles login, logout, and session verification (GET /me).
 *
 * Response shapes (from API.md):
 *   POST /login  → { token: "1|abc...", user: { id, name, email } }
 *   POST /logout → { message: "Logged out successfully" }
 *   GET  /me     → { user: { id, name, email } }
 *
 * Used by: src/stores/auth.js
 */

import http from '@/api/http.js'

/**
 * Log in with email and password.
 * @param {{ email: string, password: string }} credentials
 * @returns {Promise<{ token: string, user: object }>}
 */
export function login(credentials) {
  return http.post('/login', credentials).then((res) => res.data)
}

/**
 * Log out the current user (revokes the token on the server).
 * @returns {Promise<{ message: string }>}
 */
export function logout() {
  return http.post('/logout').then((res) => res.data)
}

/**
 * Get the currently authenticated user.
 * Used to verify that a stored token is still valid.
 * @returns {Promise<{ user: object }>}
 */
export function getMe() {
  return http.get('/me').then((res) => res.data)
}
