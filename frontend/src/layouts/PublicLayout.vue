<!--
  PublicLayout.vue — Public Site Layout Shell
  ────────────────────────────────────────────
  Wraps all public pages with the full, responsive navbar and footer.
  Fetches all portfolio data when mounted (profile, projects, skills,
  experiences, certifications, and socials).

  Provides an accessible skip-to-content target with id="main-content".
-->

<script setup>
import { defineAsyncComponent, onMounted, ref } from 'vue'
import { RouterView } from 'vue-router'
import { usePortfolioStore } from '@/stores/portfolio.js'
import TheNavbar from '@/components/layout/TheNavbar.vue'
import TheFooter from '@/components/layout/TheFooter.vue'
import { useAutoRefresh } from '@/composables/useAutoRefresh.js'

/* Lazy-load the chat widget to avoid adding to initial bundle */
const ChatWidget = defineAsyncComponent(
  () => import('@/components/assistant/ChatWidget.vue'),
)
const portfolioStore = usePortfolioStore()
const chatRequested = ref(false)
const chatReady = ref(false)

// Silently refresh portfolio data when the tab regains focus or another tab
// announces an update; the ETag keeps unchanged refreshes cheap.
useAutoRefresh()

onMounted(() => {
  portfolioStore.fetchAll()
})
</script>

<template>
  <div class="min-h-screen flex flex-col bg-bg text-fg selection:bg-accent selection:text-on-accent transition-colors">
    <!-- Main Navigation Bar -->
    <TheNavbar />

    <!-- Main Page Content -->
    <main id="main-content" class="flex-1 focus:outline-none">
      <RouterView />
    </main>

    <!-- Site Footer -->
    <TheFooter />

    <button
      v-if="portfolioStore.assistant.enabled && !chatReady"
      type="button"
      class="fixed z-[9999] flex items-center gap-2 rounded-full bg-accent px-4 py-3 text-on-accent shadow-overlay hover-button-primary focus-ring disabled:cursor-wait disabled:opacity-70"
      style="bottom: max(1rem, env(safe-area-inset-bottom, 1rem)); right: 1rem; min-height: 48px;"
      :disabled="chatRequested"
      :aria-busy="chatRequested"
      :aria-label="chatRequested ? 'Loading chat assistant' : `Ask me anything about ${portfolioStore.profile?.name?.split(' ')[0] || 'me'}`"
      @click="chatRequested = true"
    >
      <svg class="h-5 w-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z" />
      </svg>
      <span class="text-sm font-medium">{{ chatRequested ? 'Loading...' : 'Ask me anything' }}</span>
    </button>

    <ChatWidget
      v-if="chatRequested"
      :initially-open="true"
      @ready="chatReady = true"
    />
  </div>
</template>
