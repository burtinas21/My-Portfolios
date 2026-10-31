/**
 * stores/toast.js — Toast Notification Store
 * ───────────────────────────────────────────
 * Manages the stack of toast messages shown to the user.
 *
 * A toast is a small temporary message (e.g. "Saved!", "Error!")
 * that appears in the top-right corner and auto-dismisses.
 *
 * How it works:
 * 1. Any component/store calls `toastStore.show({ ... })`
 * 2. The toast gets a unique ID and is pushed to the array
 * 3. After `duration` ms, it's automatically removed
 * 4. ToastHost.vue (rendered in App.vue) displays them
 *
 * Used by: any store or component that needs to show feedback
 */

import { defineStore } from 'pinia'
import { ref } from 'vue'

/** Counter for generating unique toast IDs */
let nextId = 0

export const useToastStore = defineStore('toast', () => {
  /* ── State ─────────────────────────────────────────────────── */
  /** @type {import('vue').Ref<Array<{ id: number, type: string, message: string }>>} */
  const toasts = ref([])

  /* ── Actions ───────────────────────────────────────────────── */

  /**
   * Show a toast notification.
   *
   * @param {object} options
   * @param {'success'|'error'|'info'|'warning'} [options.type='info'] — Visual style
   * @param {string} options.message — The text to display
   * @param {number} [options.duration=4000] — How long to show it (ms)
   */
  function show({ type = 'info', message, duration = 4000 }) {
    const id = nextId++
    toasts.value.push({ id, type, message })

    // Auto-remove after the duration
    setTimeout(() => {
      dismiss(id)
    }, duration)
  }

  /**
   * Manually dismiss a toast by ID.
   * @param {number} id
   */
  function dismiss(id) {
    const index = toasts.value.findIndex((t) => t.id === id)
    if (index !== -1) {
      toasts.value.splice(index, 1)
    }
  }

  /* ── Convenience shortcuts ─────────────────────────────────── */

  /** Show a success toast */
  function success(message, duration) {
    show({ type: 'success', message, duration })
  }

  /** Show an error toast */
  function error(message, duration) {
    show({ type: 'error', message, duration })
  }

  /** Show a warning toast */
  function warning(message, duration) {
    show({ type: 'warning', message, duration })
  }

  return { toasts, show, dismiss, success, error, warning }
})
