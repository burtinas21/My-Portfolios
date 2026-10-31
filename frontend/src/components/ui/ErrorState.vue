<!--
  ErrorState.vue — Error Display with Retry
  ──────────────────────────────────────────
  Shown when an API call fails. Displays a friendly error message
  and a "Try again" button that re-triggers the fetch.

  Usage:
    <ErrorState message="Failed to load projects" @retry="fetchProjects" />

  Used by: every section that loads data from the API
-->

<script setup>
defineProps({
  /** The error message to display */
  message: { type: String, default: 'Something went wrong. Please try again.' },
})

defineEmits(['retry'])
</script>

<template>
  <div class="flex flex-col items-center justify-center gap-4 py-12 text-center">
    <!-- Warning icon -->
    <div class="rounded-full bg-danger/10 p-3">
      <svg
        class="h-6 w-6 text-danger"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
      >
        <circle cx="12" cy="12" r="10" />
        <line x1="12" y1="8" x2="12" y2="12" />
        <line x1="12" y1="16" x2="12.01" y2="16" />
      </svg>
    </div>

    <p class="text-sm text-muted max-w-xs">{{ message }}</p>

    <button
      class="inline-flex items-center gap-2 rounded-control px-4 py-2 text-sm font-medium
             bg-surface border border-line text-fg
             hover:bg-surface-2 transition-colors
             focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-bg"
      @click="$emit('retry')"
    >
      <!-- Refresh icon -->
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <path d="M21 12a9 9 0 1 1-3.42-7.07" />
        <path d="M21 3v6h-6" />
      </svg>
      Try again
    </button>
  </div>
</template>
