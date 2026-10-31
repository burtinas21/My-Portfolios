<!--
  SkillsSection.vue — Technical Skills & Expertise
  ───────────────────────────────────────────────────
  Displays technical competencies grouped by category (Frontend, Backend, etc.)
  with:
  - Category headings
  - Responsive tile grid per category (2 cols mobile → 3 sm → 5 lg)
  - SkillIcon chip on top, skill name below, level as tooltip only
  - Staggered scroll-reveal animation (respects prefers-reduced-motion)
  - Loading skeleton state, error state with retry, and empty state
-->

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { usePortfolioStore } from '@/stores/portfolio.js'
import SkeletonBlock from '@/components/ui/SkeletonBlock.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import ErrorState from '@/components/ui/ErrorState.vue'
import SkillIcon from '@/components/ui/SkillIcon.vue'

const portfolioStore = usePortfolioStore()

// Root section reference for intersection observer
const sectionRef = ref(null)
// Drive the stagger reveal — true once the section enters viewport
const isVisible = ref(false)

let observer = null

onMounted(() => {
  if ('IntersectionObserver' in window && sectionRef.value) {
    observer = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting) {
          isVisible.value = true
          observer?.disconnect()
        }
      },
      { threshold: 0.1 },
    )
    observer.observe(sectionRef.value)
  } else {
    // Fallback for environments without IntersectionObserver
    isVisible.value = true
  }
})

onUnmounted(() => {
  observer?.disconnect()
})

/**
 * Return an SVG icon key for the category card header (same helper as before).
 */
function getCategoryIcon(category) {
  const normalized = (category || '').toLowerCase()
  if (normalized.includes('back')) return 'backend'
  if (normalized.includes('front')) return 'frontend'
  if (normalized.includes('db') || normalized.includes('data')) return 'database'
  return 'tools'
}

/**
 * Compute per-tile transition-delay so tiles stagger in one-by-one.
 * Capped at 500 ms so late tiles don't feel sluggish.
 * @param {number} index — zero-based index within the category tile list
 */
function tileDelay(index) {
  return `${Math.min(index * 60, 500)}ms`
}
</script>

<template>
  <section
    id="skills"
    ref="sectionRef"
    aria-labelledby="skills-title"
    class="py-20 border-b border-line/60 bg-surface/30"
  >
    <div class="container-main space-y-12">
      <!-- Section Header -->
      <div class="space-y-2 max-w-xl">
        <span class="inline-block text-xs font-mono font-semibold uppercase tracking-wider text-accent">
          Expertise
        </span>
        <h2 id="skills-title" class="text-heading font-bold tracking-tight text-fg">
          Skills &amp; Technologies
        </h2>
        <p class="text-sm sm:text-base text-muted">
          Tools, frameworks, and programming languages applied across enterprise and personal projects.
        </p>
      </div>

      <!-- Loading Skeleton State -->
      <div
        v-if="portfolioStore.skillsLoading && !portfolioStore.skillCategories.length"
        class="space-y-8"
      >
        <div v-for="i in 2" :key="i" class="space-y-3">
          <SkeletonBlock height="1.25rem" width="6rem" />
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            <div
              v-for="j in 5"
              :key="j"
              class="skeleton rounded-card h-20"
            />
          </div>
        </div>
      </div>

      <!-- Error State with Retry -->
      <div v-else-if="portfolioStore.skillsError && !portfolioStore.skillCategories.length">
        <ErrorState
          :message="portfolioStore.skillsError"
          @retry="portfolioStore.refresh()"
        />
      </div>

      <!-- Empty State -->
      <div v-else-if="portfolioStore.skillCategories.length === 0">
        <EmptyState
          title="No skills listed"
          description="Technical skills information will be published shortly."
        />
      </div>

      <!-- Categories -->
      <div v-else class="space-y-10">
        <div
          v-for="(skillsList, category) in portfolioStore.skills"
          :key="category"
        >
          <!-- Category heading row with the same icon chip as before -->
          <div class="flex items-center gap-3 mb-4">
            <div class="w-8 h-8 rounded-control bg-accent-soft text-accent flex items-center justify-center shrink-0">
              <!-- Backend icon -->
              <svg v-if="getCategoryIcon(category) === 'backend'" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect width="20" height="8" x="2" y="2" rx="2" />
                <rect width="20" height="8" x="2" y="14" rx="2" />
                <line x1="6" x2="6.01" y1="6" y2="6" />
                <line x1="6" x2="6.01" y1="18" y2="18" />
              </svg>
              <!-- Frontend icon -->
              <svg v-else-if="getCategoryIcon(category) === 'frontend'" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="16 18 22 12 16 6" />
                <polyline points="8 6 2 12 8 18" />
              </svg>
              <!-- Database icon -->
              <svg v-else-if="getCategoryIcon(category) === 'database'" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <ellipse cx="12" cy="5" rx="9" ry="3" />
                <path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5" />
                <path d="M3 12c0 1.66 4.03 3 9 3s9-1.34 9-3" />
              </svg>
              <!-- Generic tools icon -->
              <svg v-else class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
              </svg>
            </div>
            <div>
              <h3 class="font-bold text-base text-fg">{{ category }}</h3>
              <span class="text-xs text-muted">{{ skillsList.length }} technologies</span>
            </div>
          </div>

          <!--
            Tile grid — 2 cols on phones, 3 on sm, 5 on lg.
            Using ul/li for list semantics (accessibility).
          -->
          <ul class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 list-none p-0">
            <li
              v-for="(skill, idx) in skillsList"
              :key="skill.id"
              class="skill-tile"
              :class="{ 'skill-tile--visible': isVisible }"
              :style="{ transitionDelay: tileDelay(idx) }"
              :title="`${skill.name} — ${skill.level}% proficiency`"
            >
              <SkillIcon :icon="skill.icon || ''" :name="skill.name" :size="32" />
              <span class="mt-2 text-xs font-medium text-fg text-center leading-tight line-clamp-2">
                {{ skill.name }}
              </span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
@reference "@/assets/main.css";

/*
 * Individual skill tile: icon on top, name below, subtle hover lift.
 * Starts invisible (opacity 0, slight upward offset) and reveals on .skill-tile--visible.
 * prefers-reduced-motion: transition is instant so no movement happens.
 */
.skill-tile {
  @apply flex flex-col items-center justify-center
         p-3 rounded-card
         border border-line bg-surface
         cursor-default select-none
         transition-all duration-300 ease-out;

  /* Scroll-reveal start state */
  opacity: 0;
  transform: translateY(0.75rem);
}

.skill-tile--visible {
  opacity: 1;
  transform: translateY(0);
}

/* Soft hover lift */
.skill-tile:hover {
  @apply border-accent/40 shadow-card;
  transform: translateY(-2px);
}

/* Keep hover lift disabled while not yet revealed */
.skill-tile:not(.skill-tile--visible):hover {
  transform: translateY(0.75rem);
}

@media (prefers-reduced-motion: reduce) {
  .skill-tile {
    opacity: 1;
    transform: none;
    transition: none;
  }
  .skill-tile:hover {
    transform: none;
  }
}
</style>
