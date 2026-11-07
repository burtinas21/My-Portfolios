/**
 * api/admin/cv.js — Admin CV/Resume API
 * ──────────────────────────────────────
 * GET    /admin/cv        → get CV info or { exists: false }
 * POST   /admin/cv        → upload or replace CV (multipart, field "file")
 * PATCH  /admin/cv        → toggle is_public
 * DELETE /admin/cv        → delete CV file and record
 * GET    /admin/cv/file   → preview CV inline (authenticated)
 *
 * Used by: views/admin/CvView.vue
 */

import http from '@/api/http.js'

/**
 * Get current CV info.
 * @returns {Promise<object>} — { exists, original_name?, size?, is_public?, download_count?, updated_at? }
 */
export function getCv() {
  return http.get('/admin/cv').then((res) => res.data)
}

/**
 * Upload or replace CV.
 * @param {File} file — PDF file
 * @param {function} onUploadProgress — callback for upload progress: (percentage) => void
 * @returns {Promise<object>} — Updated CV info
 */
export function uploadCv(file, onUploadProgress) {
  const formData = new FormData()
  formData.append('file', file)

  return http
    .post('/admin/cv', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (progressEvent) => {
        if (progressEvent.total) {
          const percentage = Math.round((progressEvent.loaded * 100) / progressEvent.total)
          onUploadProgress?.(percentage)
        }
      },
    })
    .then((res) => res.data)
}

/**
 * Toggle CV visibility.
 * @param {boolean} isPublic
 * @returns {Promise<object>} — Updated CV info
 */
export function toggleCvPublic(isPublic) {
  return http.patch('/admin/cv', { is_public: isPublic }).then((res) => res.data)
}

/**
 * Delete CV.
 * @returns {Promise<void>}
 */
export function deleteCv() {
  return http.delete('/admin/cv')
}

/**
 * Get CV preview URL (returns blob URL for preview).
 * @returns {Promise<string>} — Blob URL
 */
export async function getCvPreviewUrl() {
  const response = await http.get('/admin/cv/file', {
    responseType: 'blob',
  })
  return URL.createObjectURL(response.data)
}
