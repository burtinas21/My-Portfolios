<!--
  ExperienceSection.vue — Career & Experience Timeline
  ────────────────────────────────────────────────────
  Presents professional background and work history in a sleek
  vertical timeline layout:
  - Visual timeline stem with milestone indicator nodes
  - Period badges, job roles, organizations, and descriptions
  - Skeleton loading placeholders & EmptyState handling
-->

<script setup>
import { usePortfolioStore } from '@/stores/portfolio.js'
import AppContainer from '@/components/ui/AppContainer.vue'
import SkeletonBlock from '@/components/ui/SkeletonBlock.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import ErrorState from '@/components/ui/ErrorState.vue'

const portfolioStore = usePortfolioStore()
</script>

<template>
  <section
    id="experience"
    aria-labelledby="experience-title"
    class="py-20 border-b border-line/60"
  >
    <AppContainer class="space-y-12">
      <!-- Section Header -->
      <div class="space-y-2 max-w-xl">
        <span class="inline-block text-xs font-mono font-semibold uppercase tracking-wider text-accent">
          Career Path
        </span>
        <h2 id="experience-title" class="text-heading font-bold tracking-tight text-fg">
          Work Experience
        </h2>
        <p class="text-sm sm:text-base text-muted">
          Previous roles, engineering responsibilities, and team collaborations.
        </p>
      </div>

      <!-- Loading Skeleton State -->
      <div
        v-if="portfolioStore.experiencesLoading && !portfolioStore.experiences.length"
        class="space-y-6 max-w-3xl"
      >
        <div v-for="i in 3" :key="i" class="flex gap-4">
          <SkeletonBlock height="1.5rem" width="1.5rem" class="rounded-full shrink-0" />
          <div class="flex-1 space-y-3">
            <SkeletonBlock height="1.5rem" width="40%" />
            <SkeletonBlock height="1.25rem" width="25%" />
            <SkeletonBlock height="4rem" width="100%" />
          </div>
        </div>
      </div>

      <!-- Error State with Retry -->
      <div v-else-if="portfolioStore.experiencesError && !portfolioStore.experiences.length">
        <ErrorState
          :message="portfolioStore.experiencesError"
          @retry="portfolioStore.refresh()"
        />
      </div>

      <!-- Empty State -->
      <div v-else-if="portfolioStore.experiences.length === 0">
        <EmptyState
          title="No experience listed"
          description="Career details will be added soon."
        />
      </div>

      <!-- Timeline Layout -->
      <div v-else class="relative max-w-3xl pl-6 sm:pl-8 space-y-8">
        <!-- Vertical timeline stem line -->
        <div
          class="absolute top-3 bottom-3 left-2.5 sm:left-3.5 w-[2px] bg-line"
          aria-hidden="true"
        />

        <!-- Experience Items -->
        <article
          v-for="exp in portfolioStore.experiences"
          :key="exp.id"
          class="relative group"
        >
          <!-- Milestone indicator dot -->
          <div
            class="absolute -left-[23px] sm:-left-[27px] top-1.5 w-4 h-4 rounded-full bg-surface border-2 border-accent transition-transform duration-200 group-hover:scale-125"
            aria-hidden="true"
          />

          <!-- Experience Card -->
          <div
            class="rounded-card border border-line bg-surface p-6 shadow-card hover-card space-y-3"
          >
            <!-- Period and Organization Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-line">
              <div>
                <h3 class="text-base sm:text-lg font-bold text-fg">
                  {{ exp.role }}
                </h3>
                <div class="flex items-center gap-1.5 text-sm text-accent font-medium mt-0.5">
                  <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect width="16" height="20" x="4" y="2" rx="2" />
                    <path d="M9 22v-4h6v4M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01" />
                  </svg>
                  <span>{{ exp.organization }}</span>
                </div>
              </div>

              <!-- Period Pill -->
              <span class="inline-flex items-center self-start sm:self-center px-3 py-1 rounded-full text-xs font-mono font-medium bg-surface-2 border border-line text-muted">
                {{ exp.period }}
              </span>
            </div>

            <!-- Description -->
            <p
              v-if="exp.description"
              class="text-sm text-muted leading-relaxed whitespace-pre-line"
            >
              {{ exp.description }}
            </p>
          </div>
        </article>
      </div>
    </AppContainer>
  </section>
</template>
