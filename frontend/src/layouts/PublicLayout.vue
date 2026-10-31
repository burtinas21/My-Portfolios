<!--
  PublicLayout.vue — Public Site Layout Shell
  ────────────────────────────────────────────
  Wraps all public pages with the full, responsive navbar and footer.
  Fetches all portfolio data when mounted (profile, projects, skills,
  experiences, and socials).

  Provides an accessible skip-to-content target with id="main-content".
-->

<script setup>
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'
import { usePortfolioStore } from '@/stores/portfolio.js'
import TheNavbar from '@/components/layout/TheNavbar.vue'
import TheFooter from '@/components/layout/TheFooter.vue'
import { useAutoRefresh } from '@/composables/useAutoRefresh.js'

const portfolioStore = usePortfolioStore()

// Silently re-fetch portfolio data when the user switches back to this tab,
// but only if the cached data is older than 5 minutes (no timer polling).
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
  </div>
</template>
