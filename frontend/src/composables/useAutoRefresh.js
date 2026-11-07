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
 * These events trigger a silent refresh; the server's ETag makes unchanged
 * data a cheap 304 response. No timer-based polling is used.
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
  let updateChannel = null

  function onVisibilityChange() {
    if (document.visibilityState === 'visible') {
      portfolioStore.refresh({ broadcast: false })
    }
  }

  function onWindowFocus() {
    portfolioStore.refresh({ broadcast: false })
  }

  function onPortfolioUpdate(event) {
    if (event.data?.type === 'refresh') {
      portfolioStore.refresh({ broadcast: false })
    }
  }

  onMounted(() => {
    document.addEventListener('visibilitychange', onVisibilityChange)
    window.addEventListener('focus', onWindowFocus)

    if (typeof BroadcastChannel !== 'undefined') {
      updateChannel = new BroadcastChannel('portfolio-updated')
      updateChannel.addEventListener('message', onPortfolioUpdate)
    }
  })

  onUnmounted(() => {
    document.removeEventListener('visibilitychange', onVisibilityChange)
    window.removeEventListener('focus', onWindowFocus)
    updateChannel?.removeEventListener('message', onPortfolioUpdate)
    updateChannel?.close()
  })
}
