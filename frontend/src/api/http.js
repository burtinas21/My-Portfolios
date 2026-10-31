/**
 * http.js — Axios HTTP Client
 * ────────────────────────────
 * A single, shared axios instance that every API file imports.
 * This is the ONLY place in the app that talks to the backend.
 *
 * Features:
 * - Base URL from VITE_API_URL environment variable
 * - Automatic Bearer token injection from the auth store
 * - Consistent error shape: { status, message, fieldErrors }
 * - Auto-logout on 401 responses from /admin endpoints
 * - One automatic retry for GET requests on network errors
 *
 * Used by: src/api/*.js and src/api/admin/*.js (never by components directly)
 */

import axios from 'axios'

/* ── Create the axios instance ───────────────────────────────── */

const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api',
  timeout: 15000,
  headers: {
    Accept: 'application/json',
  },
})

/* ── Request interceptor: attach the auth token ──────────────── */
/*
 * Before every request, we check if the user is logged in by looking
 * for a token in localStorage. If found, we add it as a Bearer token.
 *
 * WHY localStorage instead of importing the auth store?
 * → To avoid a circular dependency: the auth store imports API files
 *   which import http.js. If http.js also imported the auth store,
 *   we'd have a circular import. localStorage breaks that cycle.
 */

http.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

/* ── Response interceptor: normalize errors ──────────────────── */
/*
 * The backend can return errors in several shapes:
 *   - 422: { message: "...", errors: { field: ["msg1", "msg2"] } }
 *   - 401: { message: "Unauthenticated." }
 *   - 429: { message: "Too Many Attempts." }
 *   - Network error: no response at all
 *
 * We normalize ALL of these into one consistent shape so that
 * stores and components always handle errors the same way:
 *
 *   { status: number|null, message: string, fieldErrors: object|null }
 *
 * We also handle two special cases:
 *   1. If a 401 comes from an /admin URL, we log the user out
 *   2. If a GET request fails due to a network error, we retry once
 */

http.interceptors.response.use(
  // Success: just return the response as-is
  (response) => response,

  // Error: normalize and handle special cases
  async (error) => {
    const { config, response } = error

    /* ─── Retry GET requests once on network errors ──────────── */
    // A network error means the request never reached the server
    // (the user's internet dropped, the server was restarting, etc.)
    // We retry GET requests exactly once because they are safe to repeat.
    if (
      !response &&
      config &&
      config.method === 'get' &&
      !config._retried  // prevent infinite retry loops
    ) {
      config._retried = true
      return http(config)
    }

    /* ─── Build a consistent error object ────────────────────── */
    const normalized = {
      status: response?.status || null,
      message: response?.data?.message || error.message || 'Something went wrong',
      fieldErrors: response?.data?.errors || null,
    }

    /* ─── Auto-logout on 401 from admin endpoints ────────────── */
    // If the server says "Unauthenticated" on an admin route, the token
    // has expired or been revoked. We clear the token and redirect to login.
    if (response?.status === 401 && config?.url?.includes('/admin')) {
      localStorage.removeItem('auth_token')

      // Dynamic import to avoid circular dependency with the auth store
      const { useAuthStore } = await import('@/stores/auth.js')
      const authStore = useAuthStore()
      authStore.$reset()

      // Redirect to login with the current path so we can return after login
      const { default: router } = await import('@/router/index.js')
      const currentPath = router.currentRoute.value.fullPath
      router.replace({ name: 'admin-login', query: { redirect: currentPath } })
    }

    // Re-throw so the calling code's catch block still fires
    return Promise.reject(normalized)
  },
)

export default http
