/**
 * api/admin/uploads.js — Admin Image Upload API
 * ──────────────────────────────────────────────
 * POST /admin/uploads — Upload a single image file (multipart/form-data)
 *
 * Returns: { url: "/storage/uploads/...", path: "uploads/..." }
 * The returned url is used in profile avatar or project image fields.
 *
 * Used by: components/admin/ImageUpload.vue
 */

import http from '@/api/http.js'

/**
 * Upload an image file.
 * @param {File} file — The image file to upload
 * @param {Function} [onProgress] — Optional progress callback (0-100)
 * @returns {Promise<{ url: string, path: string }>}
 */
export function uploadImage(file, onProgress) {
  const formData = new FormData()
  formData.append('image', file)

  return http
    .post('/admin/uploads', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (event) => {
        if (onProgress && event.total) {
          onProgress(Math.round((event.loaded * 100) / event.total))
        }
      },
    })
    .then((res) => res.data)
}
