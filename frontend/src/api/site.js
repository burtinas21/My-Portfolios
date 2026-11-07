/**
 * api/site.js — Public Site Bootstrap API
 * ────────────────────────────────────────
 * Fetches all public portfolio data in one request and revalidates it with
 * the server's weak ETag when the app refreshes the payload.
 */

import http from '@/api/http.js'

let etag = null
let lastPayload = null

export async function getSite() {
  const response = await http.get('/site', {
    headers: etag ? { 'If-None-Match': etag } : {},
    validateStatus: (status) => (status >= 200 && status < 300) || status === 304,
  })

  if (response.status === 304) {
    return lastPayload
  }

  etag = response.headers.etag || null
  lastPayload = response.data
  return lastPayload
}
