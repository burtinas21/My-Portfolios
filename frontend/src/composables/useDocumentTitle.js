/**
 * composables/useDocumentTitle.js — Document Title Management
 * ────────────────────────────────────────────────────────────
 * Sets the browser tab <title> with consistent formatting.
 *
 * Admin pages: "<Page> | <Profile Name>"
 * Public pages: "<Profile Name> | <first part of headline before |>"
 *
 * Usage:
 *   import { useDocumentTitle } from '@/composables/useDocumentTitle.js'
 *   useDocumentTitle('Dashboard')
 */

import { watch, onMounted } from 'vue'
import { usePortfolioStore } from '@/stores/portfolio.js'

/**
 * Set the document title for admin pages.
 * Format: "<Page> | <Profile Name>"
 *
 * @param {string} pageTitle - The page name (e.g., "Dashboard", "Projects")
 */
export function useDocumentTitle(pageTitle) {
  const portfolioStore = usePortfolioStore()

  function updateTitle() {
    const profileName = portfolioStore.profile?.name || 'Portfolio'
    document.title = `${pageTitle} | ${profileName}`
  }

  // Set title on mount
  onMounted(() => {
    updateTitle()
  })

  // Update if profile changes (e.g., after admin edits)
  watch(() => portfolioStore.profile?.name, () => {
    updateTitle()
  })
}

/**
 * Set the document title for public pages.
 * Format: "<Profile Name> | <first part of headline before |>"
 *
 * If headline contains " | ", uses only the part before it.
 * Example headline: "Full-Stack Developer | Laravel & Vue.js"
 * Result: "<Name> | Full-Stack Developer"
 */
export function usePublicDocumentTitle() {
  const portfolioStore = usePortfolioStore()

  function updateTitle() {
    const profileName = portfolioStore.profile?.name || 'Portfolio'
    const headline = portfolioStore.profile?.headline || 'Developer'
    
    // Extract the first part before " | " if it exists
    const headlinePart = headline.split('|')[0].trim()
    
    document.title = `${profileName} | ${headlinePart}`
  }

  // Set title on mount
  onMounted(() => {
    updateTitle()
  })

  // Update if profile changes
  watch(
    () => [portfolioStore.profile?.name, portfolioStore.profile?.headline],
    () => {
      updateTitle()
    }
  )
}
