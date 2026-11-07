<!--
  SkillsSection.vue — Technical Skills & Expertise
  ───────────────────────────────────────────────────
  Displays technical competencies grouped by category (Frontend, Backend, etc.)
  with infinite horizontal marquee animations:
  - Category headings with icons
  - Continuous horizontal scroll per category (seamless loop)
  - Alternating directions (row 1 left, row 2 right, etc.)
  - Soft fade edges using CSS mask
  - Pause on hover/focus for accessibility
  - Play/Pause button for user control
  - Respects prefers-reduced-motion (shows static grid instead)
  - Loading skeleton state, error state with retry, and empty state
  
  Technical implementation:
  - Each marquee track contains TWO identical halves
  - Each half width is measured and repeated until it exceeds container width
  - Repeat count is recalculated with ResizeObserver on window resize
  - Animation speed is constant at ~40px/sec across all rows
  - The second half has aria-hidden and tabindex=-1 for screen readers
-->

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { usePortfolioStore } from '@/stores/portfolio.js'
import AppContainer from '@/components/ui/AppContainer.vue'
import SkeletonBlock from '@/components/ui/SkeletonBlock.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import ErrorState from '@/components/ui/ErrorState.vue'
import SkillIcon from '@/components/ui/SkillIcon.vue'

const portfolioStore = usePortfolioStore()

// Root section reference for intersection observer
const sectionRef = ref(null)
// Track if section is visible (start animation only when in viewport)
const isVisible = ref(false)
// User control: pause/play all marquees
const isPaused = ref(false)
// Check if user prefers reduced motion (reactive to system setting changes)
const prefersReducedMotion = ref(false)
// Store repeat counts per category (reactive to window resize)
const categoryRepeatCounts = ref({})
// Store animation durations per category (scaled by width)
const categoryDurations = ref({})
// Refs to marquee containers for measurement
const marqueeContainerRefs = ref({})

let observer = null
let resizeObserver = null

onMounted(() => {
  // Check prefers-reduced-motion media query
  const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)')
  prefersReducedMotion.value = mediaQuery.matches

  // Listen for changes to the media query (e.g., user changes system setting)
  const handleMotionChange = (e) => { prefersReducedMotion.value = e.matches }
  mediaQuery.addEventListener('change', handleMotionChange)

  // Set up intersection observer to start/stop animation when in/out of view
  if ('IntersectionObserver' in window && sectionRef.value) {
    observer = new IntersectionObserver(
      (entries) => {
        isVisible.value = entries[0].isIntersecting
      },
      { threshold: 0.1 },
    )
    observer.observe(sectionRef.value)
  } else {
    // Fallback: assume always visible
    isVisible.value = true
  }

  // Set up ResizeObserver to recalculate on container size changes
  if ('ResizeObserver' in window) {
    resizeObserver = new ResizeObserver(() => {
      recalculateAllMarquees()
    })
    // Observe the main container
    const container = sectionRef.value?.querySelector('.app-container')
    if (container) {
      resizeObserver.observe(container)
    }
  }

  // Initial calculation
  nextTick(() => {
    recalculateAllMarquees()
  })

  // Clean up media query listener
  onUnmounted(() => {
    mediaQuery.removeEventListener('change', handleMotionChange)
  })
})

onUnmounted(() => {
  observer?.disconnect()
  resizeObserver?.disconnect()
})

// Recalculate when data changes
watch(() => portfolioStore.skills, () => {
  nextTick(() => {
    recalculateAllMarquees()
  })
}, { deep: true })

/**
 * Return an SVG icon key for the category card header.
 */
function getCategoryIcon(category) {
  const normalized = (category || '').toLowerCase()
  if (normalized.includes('back')) return 'backend'
  if (normalized.includes('front')) return 'frontend'
  if (normalized.includes('db') || normalized.includes('data')) return 'database'
  return 'tools'
}

/**
 * Base animation speed in pixels per second (target velocity).
 */
const BASE_SPEED_PX_PER_SEC = 40

/**
 * Determine animation direction: even rows go left, odd rows go right.
 */
function getAnimationDirection(index) {
  return index % 2 === 0 ? 'left' : 'right'
}

/**
 * Recalculate repeat counts and durations for all marquee rows.
 * Measures container width and skill item width to determine optimal repetitions.
 * Each half of the track must be wider than the container to prevent gaps.
 */
function recalculateAllMarquees() {
  categoriesArray.value.forEach(categoryData => {
    const containerEl = marqueeContainerRefs.value[categoryData.name]
    if (!containerEl) return

    const containerWidth = containerEl.offsetWidth
    if (!containerWidth) return

    // Create a temporary wrapper to measure one full copy of skills
    const tempWrapper = document.createElement('div')
    tempWrapper.style.position = 'absolute'
    tempWrapper.style.visibility = 'hidden'
    tempWrapper.style.display = 'flex'
    tempWrapper.style.gap = '0.75rem' // Match .marquee-track gap
    
    // Create skill pills to measure
    categoryData.skills.forEach(skill => {
      const pill = document.createElement('div')
      pill.style.minWidth = '8rem'
      pill.style.maxWidth = '12rem'
      pill.style.padding = '0.5rem 0.75rem'
      pill.style.border = '1px solid transparent'
      pill.textContent = skill.name
      tempWrapper.appendChild(pill)
    })

    containerEl.appendChild(tempWrapper)
    const copyWidth = tempWrapper.offsetWidth
    containerEl.removeChild(tempWrapper)

    // Calculate repetitions: each HALF must be wider than container
    // Add +1 to ensure no gaps even with rounding/subpixel issues
    const repeatsNeeded = Math.max(1, Math.ceil(containerWidth / copyWidth) + 1)
    
    // Store repeat count
    categoryRepeatCounts.value[categoryData.name] = repeatsNeeded

    // Calculate animation duration based on one half width (~40px/sec)
    const halfWidth = repeatsNeeded * copyWidth
    const duration = halfWidth / BASE_SPEED_PX_PER_SEC
    
    categoryDurations.value[categoryData.name] = duration
  })
}

/**
 * Get repeated skills array for one half of the marquee track.
 * Uses the calculated repeat count to ensure each half is wider than the container.
 * Falls back to at least 12 total items if measurement hasn't completed yet.
 */
function getRepeatedSkills(categoryName, skills) {
  const repeats = categoryRepeatCounts.value[categoryName] || Math.max(1, Math.ceil(12 / skills.length))
  return Array(repeats).fill(skills).flat()
}

/**
 * Get animation duration for a category (calculated based on width).
 * Fallback to 35s if not yet calculated.
 */
function getAnimationDuration(categoryName) {
  return categoryDurations.value[categoryName] || 35
}

/**
 * Set ref for marquee container (used for measurements).
 */
function setMarqueeRef(el, categoryName) {
  if (el) {
    marqueeContainerRefs.value[categoryName] = el
  }
}

/**
 * Toggle pause/play for all marquees.
 */
function togglePause() {
  isPaused.value = !isPaused.value
}

/**
 * Compute categories array with indices for iteration.
 */
const categoriesArray = computed(() => {
  return Object.keys(portfolioStore.skills).map((category, index) => ({
    name: category,
    skills: portfolioStore.skills[category],
    index,
  }))
})
</script>

<template>
  <section
    id="skills"
    ref="sectionRef"
    aria-labelledby="skills-title"
    class="py-20 border-b border-line/60 bg-surface/30"
  >
    <AppContainer class="space-y-12">
      <!-- Section Header -->
      <div class="flex items-center justify-between gap-4 max-w-full">
        <div class="space-y-2 flex-1 max-w-xl">
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

        <!-- Pause/Play Button (only shown when marquee is active) -->
        <button
          v-if="!prefersReducedMotion && portfolioStore.skillCategories.length > 0"
          type="button"
          class="pause-play-button"
          :class="{ 'is-paused': isPaused }"
          :aria-pressed="isPaused"
          :aria-label="isPaused ? 'Play skills animation' : 'Pause skills animation'"
          :title="isPaused ? 'Play skills animation' : 'Pause skills animation'"
          @click="togglePause"
        >
          <!-- Pause icon -->
          <svg v-if="!isPaused" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="6" y="4" width="4" height="16" rx="1" />
            <rect x="14" y="4" width="4" height="16" rx="1" />
          </svg>
          <!-- Play icon -->
          <svg v-else class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polygon points="5 3 19 12 5 21 5 3" fill="currentColor" />
          </svg>
        </button>
      </div>

      <!-- Loading Skeleton State -->
      <div
        v-if="portfolioStore.skillsLoading && !portfolioStore.skillCategories.length"
        class="space-y-8"
      >
        <div v-for="i in 2" :key="i" class="space-y-3">
          <SkeletonBlock height="1.25rem" width="6rem" />
          <div class="skeleton rounded-card h-20" />
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

      <!-- Categories (Marquee or Static Grid based on motion preference) -->
      <div v-else class="space-y-10">
        <div
          v-for="categoryData in categoriesArray"
          :key="categoryData.name"
        >
          <!-- Category heading row -->
          <div class="flex items-center gap-3 mb-4">
            <div class="w-8 h-8 rounded-control bg-accent-soft text-accent flex items-center justify-center shrink-0">
              <!-- Backend icon -->
              <svg v-if="getCategoryIcon(categoryData.name) === 'backend'" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect width="20" height="8" x="2" y="2" rx="2" />
                <rect width="20" height="8" x="2" y="14" rx="2" />
                <line x1="6" x2="6.01" y1="6" y2="6" />
                <line x1="6" x2="6.01" y1="18" y2="18" />
              </svg>
              <!-- Frontend icon -->
              <svg v-else-if="getCategoryIcon(categoryData.name) === 'frontend'" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="16 18 22 12 16 6" />
                <polyline points="8 6 2 12 8 18" />
              </svg>
              <!-- Database icon -->
              <svg v-else-if="getCategoryIcon(categoryData.name) === 'database'" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
              <h3 class="font-bold text-base text-fg">{{ categoryData.name }}</h3>
              <span class="text-xs text-muted">{{ categoryData.skills.length }} technologies</span>
            </div>
          </div>

          <!-- Marquee Container (visible when motion is enabled) -->
          <div
            v-if="!prefersReducedMotion"
            :ref="(el) => setMarqueeRef(el, categoryData.name)"
            class="marquee-container"
            :class="{
              'marquee-paused': isPaused || !isVisible,
            }"
          >
            <!-- Accessible list for screen readers (only one copy, reads each skill once) -->
            <ul class="sr-only">
              <li v-for="skill in categoryData.skills" :key="skill.id">
                {{ skill.name }}
              </li>
            </ul>

            <!-- Visual marquee track (two identical halves for seamless loop) -->
            <div
              class="marquee-track"
              :class="`marquee-${getAnimationDirection(categoryData.index)}`"
              :style="{
                '--animation-duration': `${getAnimationDuration(categoryData.name)}s`,
              }"
            >
              <!-- First half -->
              <div
                v-for="(skill, idx) in getRepeatedSkills(categoryData.name, categoryData.skills)"
                :key="`${skill.id}-first-${idx}`"
                class="skill-pill"
                tabindex="0"
                :title="`${skill.name} — ${skill.level}% proficiency`"
              >
                <SkillIcon :icon="skill.icon || ''" :name="skill.name" :size="24" />
                <span class="text-xs font-medium text-fg whitespace-nowrap">
                  {{ skill.name }}
                </span>
              </div>

              <!-- Second half (exact duplicate, hidden from screen readers) -->
              <div
                v-for="(skill, idx) in getRepeatedSkills(categoryData.name, categoryData.skills)"
                :key="`${skill.id}-second-${idx}`"
                class="skill-pill"
                tabindex="-1"
                aria-hidden="true"
                :title="`${skill.name} — ${skill.level}% proficiency`"
              >
                <SkillIcon :icon="skill.icon || ''" :name="skill.name" :size="24" />
                <span class="text-xs font-medium text-fg whitespace-nowrap">
                  {{ skill.name }}
                </span>
              </div>
            </div>
          </div>

          <!-- Static Grid (visible when reduced motion is preferred) -->
          <ul
            v-else
            class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 list-none p-0"
          >
            <li
              v-for="skill in categoryData.skills"
              :key="skill.id"
              class="skill-tile-static"
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
    </AppContainer>
  </section>
</template>

<style scoped>
@reference "@/assets/main.css";

/* ─────────────────────────────────────────────────────────────────────────────────────
   Pause/Play Button - Small (36px), subtle, icon-only control
   ───────────────────────────────────────────────────────────────────────────────────── */

.pause-play-button {
  @apply flex items-center justify-center
         shrink-0
         text-muted;
  /* Fixed 36px size, icon-only */
  width: 2.25rem;
  height: 2.25rem;
  border-radius: var(--radius-control);
  border: 1px solid var(--color-line);
  background-color: transparent;
  transition: color var(--transition-hover),
              border-color var(--transition-hover),
              background-color var(--transition-hover);
  cursor: pointer;
}

/* Hover state - accent color */
@media (hover: hover) {
  .pause-play-button:hover {
    @apply text-accent border-accent;
    background-color: var(--color-accent-soft);
  }
}

/* Focus visible (keyboard only, not mouse) */
.pause-play-button:focus-visible {
  outline: 2px solid var(--color-accent);
  outline-offset: 2px;
}

/* Remove focus outline after mouse click */
.pause-play-button:focus:not(:focus-visible) {
  outline: none;
}

/* ─────────────────────────────────────────────────────────────
   Marquee Container (overflow hidden + fade edges)
   ───────────────────────────────────────────────────────────── */

.marquee-container {
  position: relative;
  overflow: hidden;
  /* Soft fade on left and right edges using mask */
  -webkit-mask-image: linear-gradient(
    to right,
    transparent 0%,
    black 5%,
    black 95%,
    transparent 100%
  );
  mask-image: linear-gradient(
    to right,
    transparent 0%,
    black 5%,
    black 95%,
    transparent 100%
  );
}

/* ─────────────────────────────────────────────────────────────
   Marquee Track (the scrolling container)
   ───────────────────────────────────────────────────────────── */

.marquee-track {
  display: flex;
  gap: 0.75rem;
  /* will-change applied only during animation for performance */
  will-change: transform;
}

/* Animation runs only when section is visible and not paused */
.marquee-container:not(.marquee-paused) .marquee-track {
  animation: marquee-scroll var(--animation-duration) linear infinite;
}

/* Stop animation when paused or out of view (battery saving) */
.marquee-paused .marquee-track {
  animation-play-state: paused;
}

/* Pause on hover or when any item inside has focus */
.marquee-container:hover .marquee-track,
.marquee-container:focus-within .marquee-track {
  animation-play-state: paused;
}

/* ─────────────────────────────────────────────────────────────
   Marquee Animations (left and right)
   ───────────────────────────────────────────────────────────── */

/* Scroll to the left (items move leftward) */
@keyframes marquee-scroll {
  from {
    transform: translateX(0);
  }
  to {
    transform: translateX(-50%);
  }
}

/* Right-scrolling rows use reversed keyframes */
.marquee-right {
  flex-direction: row-reverse;
}

.marquee-container:not(.marquee-paused) .marquee-right {
  animation-name: marquee-scroll-reverse;
}

@keyframes marquee-scroll-reverse {
  from {
    transform: translateX(-50%);
  }
  to {
    transform: translateX(0);
  }
}

/* ─────────────────────────────────────────────────────────────
   Skill Pill (compact card with icon + name)
   ───────────────────────────────────────────────────────────── */

.skill-pill {
  @apply flex items-center gap-2
         px-3 py-2 rounded-control
         border border-line bg-surface
         cursor-default select-none
         shrink-0;
  /* Fixed width ensures consistent sizing across all items */
  min-width: 8rem;
  max-width: 12rem;
}

.skill-pill:focus-visible {
  @apply outline-none;
  box-shadow: var(--ring-focus);
}

/* ─────────────────────────────────────────────────────────────
   Static Grid (for prefers-reduced-motion)
   ───────────────────────────────────────────────────────────── */

.skill-tile-static {
  @apply flex flex-col items-center justify-center
         p-3 rounded-card
         border border-line bg-surface
         cursor-default select-none;
  /* Apply stronger hover-card effect (matches main.css hover-card utility) */
  transition: transform var(--transition-hover),
              border-color var(--transition-hover),
              box-shadow var(--transition-hover);
}

@media (hover: hover) {
  .skill-tile-static:hover,
  .skill-tile-static:focus-visible {
    transform: translateY(-4px);
    border-color: color-mix(in srgb, var(--color-accent) 60%, transparent);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12), 0 4px 8px rgba(0, 0, 0, 0.08);
  }

  .dark .skill-tile-static:hover,
  .dark .skill-tile-static:focus-visible {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4), 0 4px 8px rgba(0, 0, 0, 0.3);
  }
}

/* ─────────────────────────────────────────────────────────────────────────────────────
   Reduced Motion (disable ALL marquee animations and hover movement)
   ───────────────────────────────────────────────────────────────────────────────────── */

@media (prefers-reduced-motion: reduce) {
  .marquee-track {
    animation: none !important;
    will-change: auto;
  }
  
  .skill-tile-static:hover,
  .skill-tile-static:focus-visible {
    transform: none;
  }
}

/* ─────────────────────────────────────────────────────────────
   Screen Reader Only Utility
   ───────────────────────────────────────────────────────────── */

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}
</style>
