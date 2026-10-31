/**
 * stores/auth.js — Authentication Store
 * ──────────────────────────────────────
 * Manages the admin user's login state: token, user info,
 * login/logout actions, and session restoration on page load.
 *
 * The token is stored in both Pinia (for reactivity) and localStorage
 * (for persistence across page refreshes). The http.js interceptor reads
 * the token from localStorage to avoid circular imports.
 *
 * Session restore flow (called on app startup):
 * 1. Check if there's a token in localStorage
 * 2. If yes, call GET /me to verify it's still valid
 * 3. If /me succeeds, populate the user state
 * 4. If /me fails (401), clear everything
 *
 * Used by: router guard, AdminLayout, LoginView
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { login as apiLogin, logout as apiLogout, getMe } from '@/api/auth.js'

export const useAuthStore = defineStore('auth', () => {
  /* ── State ─────────────────────────────────────────────────── */

  /** The Sanctum bearer token (null when not logged in) */
  const token = ref(localStorage.getItem('auth_token') || null)

  /** The logged-in user object { id, name, email } */
  const user = ref(null)

  /** Whether we're currently checking the session (calling /me) */
  const loading = ref(false)

  /* ── Getters (computed) ────────────────────────────────────── */

  /** True when the user is authenticated */
  const isLoggedIn = computed(() => !!token.value)

  /* ── Actions ───────────────────────────────────────────────── */

  /**
   * Log in with email and password.
   * Saves the token to both Pinia state and localStorage.
   *
   * @param {{ email: string, password: string }} credentials
   * @throws {object} — Normalized error from http.js
   */
  async function login(credentials) {
    // The API returns { token: "1|abc...", user: { id, name, email } }
    const data = await apiLogin(credentials)

    token.value = data.token
    user.value = data.user
    localStorage.setItem('auth_token', data.token)
  }

  /**
   * Log out: tell the server to revoke the token, then clear local state.
   */
  async function logout() {
    try {
      await apiLogout()
    } catch {
      // Even if the server call fails (e.g. token already expired),
      // we still want to clear the local state
    }

    token.value = null
    user.value = null
    localStorage.removeItem('auth_token')
  }

  /**
   * Restore session on page load.
   * If there's a token in localStorage, verify it by calling GET /me.
   * If the token is invalid, clear everything silently.
   *
   * @returns {Promise<boolean>} — true if session was restored successfully
   */
  async function restoreSession() {
    if (!token.value) return false

    loading.value = true
    try {
      const data = await getMe()
      user.value = data.user
      return true
    } catch {
      // Token is invalid or expired — clean up
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
      return false
    } finally {
      loading.value = false
    }
  }

  /* ── $reset (used by http.js on 401 auto-logout) ───────────── */
  function $reset() {
    token.value = null
    user.value = null
  }

  return {
    token,
    user,
    loading,
    isLoggedIn,
    login,
    logout,
    restoreSession,
    $reset,
  }
})
