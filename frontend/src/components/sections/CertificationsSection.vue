<!--
  CertificationsSection.vue — Public Achievements
  ───────────────────────────────────────────────
  Shows published certifications, with loading skeletons and expandable results.
-->

<script setup>
import { computed, ref } from 'vue'
import { usePortfolioStore } from '@/stores/portfolio.js'
import AppContainer from '@/components/ui/AppContainer.vue'
import CertificationCard from '@/components/sections/CertificationCard.vue'

const portfolioStore = usePortfolioStore()
const showAll = ref(false)
const visibleCertifications = computed(() => (
  showAll.value
    ? portfolioStore.certifications
    : portfolioStore.certifications.slice(0, 6)
))
</script>

<template>
  <section
    v-if="portfolioStore.certificationsLoading || portfolioStore.certifications.length > 0"
    id="certifications"
    aria-labelledby="certifications-title"
    class="border-b border-line/60 py-20"
  >
    <AppContainer class="space-y-12">
      <header class="max-w-xl space-y-2">
        <span class="inline-block font-mono text-xs font-semibold uppercase text-accent">Professional milestones</span>
        <h2 id="certifications-title" class="text-heading font-bold text-fg">Achievements</h2>
        <p class="text-sm text-muted sm:text-base">Certifications, awards, and professional milestones I have earned.</p>
      </header>

      <ul
        v-if="portfolioStore.certificationsLoading && portfolioStore.certifications.length === 0"
        class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
        aria-label="Loading achievements"
        aria-hidden="true"
      >
        <li v-for="item in 6" :key="item" class="min-h-[18rem] rounded-2xl border border-line bg-surface p-6">
          <div class="flex items-center gap-3">
            <div class="skeleton h-12 w-12 rounded-lg" />
            <div class="skeleton h-3 w-28" />
          </div>
          <div class="skeleton mt-5 h-5 w-4/5" />
          <div class="skeleton mt-2 h-5 w-3/5" />
          <div class="skeleton mt-5 h-4 w-2/3" />
          <div class="skeleton mt-4 h-10 w-full" />
        </li>
      </ul>

      <template v-else>
        <TransitionGroup
          id="certifications-list"
          tag="ul"
          name="certification-list"
          class="grid grid-cols-1 items-stretch gap-6 sm:grid-cols-2 lg:grid-cols-3"
          aria-label="Achievements"
        >
          <li v-for="(certification, index) in visibleCertifications" :key="certification.id" class="h-full">
            <CertificationCard
              :certification="certification"
              :reveal-delay="Math.min(index, 7) * 70"
            />
          </li>
        </TransitionGroup>

        <div v-if="portfolioStore.certifications.length > 6" class="flex justify-center">
          <button
            type="button"
            class="min-h-11 rounded-control border border-line px-5 py-2 text-sm font-medium text-fg hover-button-outlined focus-ring"
            aria-controls="certifications-list"
            :aria-expanded="showAll"
            @click="showAll = !showAll"
          >
            {{ showAll ? 'Show fewer' : `Show all ${portfolioStore.certifications.length} achievements` }}
          </button>
        </div>
      </template>
    </AppContainer>
  </section>
</template>

<style scoped>
.certification-list-enter-active,
.certification-list-leave-active,
.certification-list-move {
  transition: opacity 220ms ease, transform 220ms ease;
}

.certification-list-enter-from,
.certification-list-leave-to {
  opacity: 0;
  transform: translateY(0.5rem);
}

@media (prefers-reduced-motion: reduce) {
  .certification-list-enter-active,
  .certification-list-leave-active,
  .certification-list-move {
    transition: none;
  }
}
</style>