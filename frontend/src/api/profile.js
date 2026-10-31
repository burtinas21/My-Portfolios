/**
 * api/profile.js — Public Profile API
 * ────────────────────────────────────
 * Fetches the portfolio owner's profile information.
 *
 * Response shape (from API.md):
 *   GET /profile → { id, name, initials, headline, bio, email, location, status, avatar, created_at, updated_at }
 *
 * Note: avatar is a RELATIVE path like "/storage/uploads/avatar.png".
 * Use imageUrl() from src/utils/url.js to get the full URL.
 *
 * Used by: src/stores/portfolio.js
 */

import http from '@/api/http.js'

/**
 * Get the public profile.
 * @returns {Promise<object>} — The profile object (may be null if no profile exists yet)
 */
export function getProfile() {
  return http.get('/profile').then((res) => res.data)
}
