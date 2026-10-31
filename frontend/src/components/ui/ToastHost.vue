<!--
  ToastHost.vue — Toast Notification Container
  ─────────────────────────────────────────────
  Renders the stack of active toasts from the toast store.
  This component should be placed ONCE in App.vue so toasts
  appear on every page.

  Toasts appear in the top-right corner and auto-dismiss.
  Each toast has a colored icon based on its type (success/error/warning/info).

  Used by: App.vue (rendered once at the root level)
-->

<script setup>
import { useToastStore } from '@/stores/toast.js'

const toastStore = useToastStore()

/**
 * Map toast types to their visual properties.
 * Each type gets a unique icon color and SVG path.
 */
const typeConfig = {
  success: {
    bgClass: 'bg-success/10 text-success',
    icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
  },
  error: {
    bgClass: 'bg-danger/10 text-danger',
    icon: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
  },
  warning: {
    bgClass: 'bg-warning/10 text-warning',
    icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
  },
  info: {
    bgClass: 'bg-accent/10 text-accent',
    icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
  },
}
</script>

<template>
  <!-- Toast container: fixed in the top-right corner -->
  <div
    class="fixed top-4 right-4 z-[60] flex flex-col gap-2 pointer-events-none"
    aria-live="polite"
    aria-label="Notifications"
  >
    <TransitionGroup name="toast">
      <div
        v-for="toast in toastStore.toasts"
        :key="toast.id"
        class="pointer-events-auto flex items-start gap-3
               rounded-card bg-surface border border-line shadow-overlay
               px-4 py-3 min-w-[280px] max-w-sm"
      >
        <!-- Type icon -->
        <div
          :class="[
            'shrink-0 rounded-full p-1',
            typeConfig[toast.type]?.bgClass || typeConfig.info.bgClass,
          ]"
        >
          <svg
            class="h-4 w-4"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
          >
            <path :d="typeConfig[toast.type]?.icon || typeConfig.info.icon" />
          </svg>
        </div>

        <!-- Message -->
        <p class="flex-1 text-sm text-fg leading-snug">{{ toast.message }}</p>

        <!-- Dismiss button -->
        <button
          class="shrink-0 rounded p-0.5 text-muted hover:text-fg transition-colors"
          aria-label="Dismiss notification"
          @click="toastStore.dismiss(toast.id)"
        >
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M18 6 6 18M6 6l12 12" />
          </svg>
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>
