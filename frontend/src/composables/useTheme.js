/**
 * composables/useTheme.js — Theme Management
 * ────────────────────────────────────────────
 * Handles light/dark/system theme switching with three modes:
 *   - 'light'  → always light
 *   - 'dark'   → always dark
 *   - 'system' → follows the OS setting via prefers-color-scheme
 *
 * How it works:
 * 1. On load, read the saved preference from localStorage (default: 'system')
 * 2. Determine the effective theme (light or dark) and toggle the 'dark' class on <html>
 * 3. Listen for OS theme changes when in 'system' mode
 * 4. When the user switches, save to localStorage and update immediately
 *
 * The Tailwind v4 @custom-variant picks up the 'dark' class automatically.
 *
 * Used by: ThemeToggle.vue, App.vue (to initialize on startup)
 */

import { ref, computed, watch, onUnmounted } from 'vue'

/** localStorage key for the theme preference */
const STORAGE_KEY = 'theme-preference'

/** The user's chosen mode: 'light', 'dark', or 'system' */
const mode = ref(localStorage.getItem(STORAGE_KEY) || 'system')

/** The media query that detects the OS dark mode setting */
const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')

/**
 * Determine the effective theme based on the current mode.
 * If mode is 'system', check the OS preference.
 * @returns {'light'|'dark'}
 */
function resolveTheme() {
  if (mode.value === 'system') {
    return mediaQuery.matches ? 'dark' : 'light'
  }
  return mode.value
}

/** The currently applied theme: 'light' or 'dark' */
const effectiveTheme = ref(resolveTheme())

/**
 * Apply the theme by toggling the 'dark' class on <html>.
 * Also updates the theme-color meta tag so the browser chrome matches.
 */
function applyTheme() {
  effectiveTheme.value = resolveTheme()
  const html = document.documentElement

  if (effectiveTheme.value === 'dark') {
    html.classList.add('dark')
  } else {
    html.classList.remove('dark')
  }

  // Update the theme-color meta tag for mobile browser chrome
  const metaThemeColor = document.querySelector('meta[name="theme-color"]')
  if (metaThemeColor) {
    metaThemeColor.setAttribute(
      'content',
      effectiveTheme.value === 'dark' ? '#0B1220' : '#F7F8FA',
    )
  }
}

// Apply on first load
applyTheme()

// Listen for OS theme changes (only matters when mode is 'system')
function handleMediaChange() {
  if (mode.value === 'system') {
    applyTheme()
  }
}
mediaQuery.addEventListener('change', handleMediaChange)

/**
 * useTheme composable
 *
 * @returns {{ mode, effectiveTheme, isDark, setMode, cycleMode }}
 */
export function useTheme() {
  /* Re-apply whenever the mode changes */
  watch(mode, () => {
    localStorage.setItem(STORAGE_KEY, mode.value)
    applyTheme()
  })

  /** True when the effective (visible) theme is dark */
  const isDark = computed(() => effectiveTheme.value === 'dark')

  /**
   * Set the theme mode directly.
   * @param {'light'|'dark'|'system'} newMode
   */
  function setMode(newMode) {
    mode.value = newMode
  }

  /**
   * Cycle through modes: system → light → dark → system.
   * This is the action triggered by the ThemeToggle button.
   */
  function cycleMode() {
    const order = ['system', 'light', 'dark']
    const currentIndex = order.indexOf(mode.value)
    mode.value = order[(currentIndex + 1) % order.length]
  }

  return {
    mode,
    effectiveTheme,
    isDark,
    setMode,
    cycleMode,
  }
}
