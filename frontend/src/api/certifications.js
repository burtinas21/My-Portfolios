/**
 * api/certifications.js — Public Certifications API
 * ─────────────────────────────────────────────────
 * Fetch published certifications for the credentials section.
 */

import http from '@/api/http.js'

export function getCertifications() {
  return http.get('/certifications').then((res) => res.data)
}