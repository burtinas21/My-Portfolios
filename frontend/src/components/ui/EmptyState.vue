<!--
  EmptyState.vue — Empty Data Display
  ────────────────────────────────────
  Shown when an API call succeeds but returns no data.
  For example: no projects yet, no skills yet, etc.

  Usage:
    <EmptyState icon="folder" message="No projects yet" />

  Used by: every section that can have zero items
-->

<script setup>
defineProps({
  /** The message to display */
  message: { type: String, default: 'Nothing here yet.' },
  /** Optional sub-text for additional context */
  description: { type: String, default: '' },
})
</script>

<template>
  <div class="flex flex-col items-center justify-center gap-3 py-12 text-center">
    <!-- Empty box icon -->
    <div class="rounded-full bg-surface-2 p-4">
      <svg
        class="h-8 w-8 text-muted"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.5"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
      >
        <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
      </svg>
    </div>

    <p class="text-sm font-medium text-muted">{{ message }}</p>
    <p v-if="description" class="text-xs text-muted/70 max-w-xs">{{ description }}</p>
  </div>
</template>
