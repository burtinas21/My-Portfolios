/**
 * composables/useToast.js — Toast Shortcut Composable
 * ────────────────────────────────────────────────────
 * A thin wrapper around the toast store for convenient use in components.
 * Instead of importing the store everywhere, components can do:
 *
 *   const toast = useToast()
 *   toast.success('Saved!')
 *   toast.error('Something went wrong')
 *
 * Used by: any component or composable that needs to show toast messages
 */

import { useToastStore } from '@/stores/toast.js'

/**
 * @returns {{ success, error, warning, show, dismiss }}
 */
export function useToast() {
  const store = useToastStore()

  return {
    success: store.success,
    error: store.error,
    warning: store.warning,
    show: store.show,
    dismiss: store.dismiss,
  }
}
