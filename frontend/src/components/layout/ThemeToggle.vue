<!--
  ThemeToggle.vue — Theme Switcher Button
  ────────────────────────────────────────
  A button that cycles through three theme modes:
    system (monitor icon) → light (sun) → dark (moon) → system ...

  Shows a tooltip-like label below with the current mode name.
  Uses the useTheme composable for the actual logic.

  Used by: TheNavbar (public site), AdminLayout (admin panel)
-->

<script setup>
import { useTheme } from '@/composables/useTheme.js'

const { mode, cycleMode } = useTheme()

/**
 * Labels for each mode (shown as aria-label and tooltip).
 */
const modeLabels = {
  system: 'System theme',
  light: 'Light theme',
  dark: 'Dark theme',
}
</script>

<template>
  <button
    class="relative rounded-control p-2 text-muted hover:text-fg hover:bg-surface-2
           transition-colors focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-bg"
    :aria-label="`Current: ${modeLabels[mode]}. Click to switch.`"
    :title="modeLabels[mode]"
    @click="cycleMode"
  >
    <!-- System icon (monitor) -->
    <svg
      v-if="mode === 'system'"
      class="h-5 w-5"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
      aria-hidden="true"
    >
      <rect x="2" y="3" width="20" height="14" rx="2" />
      <path d="M8 21h8M12 17v4" />
    </svg>

    <!-- Light icon (sun) -->
    <svg
      v-else-if="mode === 'light'"
      class="h-5 w-5"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
      aria-hidden="true"
    >
      <circle cx="12" cy="12" r="5" />
      <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
    </svg>

    <!-- Dark icon (moon) -->
    <svg
      v-else
      class="h-5 w-5"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
      aria-hidden="true"
    >
      <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
    </svg>
  </button>
</template>
