<!--
  NotFoundView.vue — 404 Page Not Found
  ───────────────────────────────────────
  Displayed when a visitor attempts to access an unknown route.
  Uses the global design tokens and provides clear navigation back home.
-->

<script setup>
import { RouterLink } from 'vue-router'
import BaseButton from '@/components/ui/BaseButton.vue'
</script>

<template>
  <div class="min-h-[70vh] flex items-center justify-center py-16 px-4">
    <div class="max-w-md w-full text-center space-y-6">
      <!-- 404 Accent Badge -->
      <div class="inline-flex items-center justify-center w-20 h-20 rounded-card bg-accent-soft text-accent border border-accent/20 font-mono text-3xl font-black shadow-card">
        404
      </div>

      <div class="space-y-2">
        <h1 class="text-3xl font-bold text-fg tracking-tight">
          Page Not Found
        </h1>
        <p class="text-sm sm:text-base text-muted leading-relaxed">
          The page or resource you are looking for doesn't exist or may have been moved.
        </p>
      </div>

      <!-- Action Button -->
      <div>
        <RouterLink to="/" class="inline-block">
          <BaseButton variant="primary" size="md">
            <svg class="w-4 h-4 mr-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
              <polyline points="9 22 9 12 15 12 15 22" />
            </svg>
            <span>Return to Portfolio</span>
          </BaseButton>
        </RouterLink>
      </div>
    </div>
  </div>
</template>