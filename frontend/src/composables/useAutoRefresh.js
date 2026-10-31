/**
 * composables/useAutoRefresh.js — Silent Background Refresh
 * ──────────────────────────────────────────────────────────
 * Listens to two browser signals that indicate the user is "coming back"
 * to the tab:
 *
 *   1. document "visibilitychange"  — fires when the tab becomes visible
 *      (user switches back from another tab).
 *   2. window "focus"               — fires when the browser window
 *      regains focus (user alt-tabs back from another app).
 *
 * On either event, it calls portfolioStore.refreshIfStale(), which only
 * hits the network if the cached data is older than the configured max
 * age.  No timer-based polling is used.
 *
 * Listeners are cleaned up automatically when the host component unmounts.
 *
 * Usage:
 *   Import and call once inside a layout component that wraps all public
 *   pages (e.g. PublicLayout.vue):
 *
 *     import { useAutoRefresh } from '@/composables/useAutoRefresh.js'
 *     useAutoRefresh()
 */

import { onMounted, onUnmounted } from 'vue'
import { usePortfolioStore } from '@/stores/portfolio.js'

export function useAutoRefresh() {
  const portfolioStore = usePortfolioStore()

  /**
   * Fires when the browser tab becomes visible again.
   * Only triggers a network request if the data is stale.
   */
  function onVisibilityChange() {
    if (document.visibilityState === 'visible') {
      portfolioStore.refreshIfStale()
    }
  }

  /**
   * Fires when the browser window gains focus (e.g. alt-tab back).
   * Also triggers a staleness check — this covers the case where
   * visibilitychange doesn't fire (e.g. same-window focus shift).
   */
  function onWindowFocus() {
    portfolioStore.refreshIfStale()
  }

  // Register event listeners when the host component mounts
  onMounted(() => {
    document.addEventListener('visibilitychange', onVisibilityChange)
    window.addEventListener('focus', onWindowFocus)
  })

  // Clean up listeners when the host component unmounts to prevent leaks
  onUnmounted(() => {
    document.removeEventListener('visibilitychange', onVisibilityChange)
    window.removeEventListener('focus', onWindowFocus)
  })
}
