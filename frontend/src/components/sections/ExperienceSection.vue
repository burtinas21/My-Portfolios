<!--
  ExperienceSection.vue — Career & Experience Timeline
  ────────────────────────────────────────────────────
  Presents professional background in a responsive vertical timeline:
  - Current role, dates, highlights, and technologies
  - Scroll-driven timeline progress and reveal effects
  - Skeleton loading and retryable error states
-->

<script setup>
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { usePortfolioStore } from '@/stores/portfolio.js'
import { formatPeriod } from '@/utils/format.js'
import AppContainer from '@/components/ui/AppContainer.vue'
import SkeletonBlock from '@/components/ui/SkeletonBlock.vue'
import ErrorState from '@/components/ui/ErrorState.vue'

const portfolioStore = usePortfolioStore()
const timelineRef = ref(null)
const timelineLineRef = ref(null)
const prefersReducedMotion = ref(false)
const expandedDescriptions = ref({})

let itemObserver = null
let motionQuery = null
let animationFrame = null

function monthKey(value) {
  return typeof value === 'string' && /^\d{4}-\d{2}/.test(value)
    ? value.slice(0, 7)
    : ''
}

function periodParts(experience) {
  if (!experience.start_date) {
    return { legacy: experience.period || '', start: '', end: '', duration: '' }
  }

  const label = formatPeriod(
    experience.start_date,
    experience.end_date,
    Boolean(experience.is_current),
  )
  const [range = '', duration = ''] = label.split(' · ')
  const [start = '', end = ''] = range.split(' – ')

  return { legacy: '', start, end, duration }
}

function updateTimelineProgress() {
  animationFrame = null
  if (!timelineRef.value || !timelineLineRef.value || prefersReducedMotion.value) return

  const rect = timelineRef.value.getBoundingClientRect()
  const progress = Math.min(1, Math.max(0, (window.innerHeight * 0.7 - rect.top) / rect.height))
  timelineLineRef.value.style.transform = `scaleY(${progress})`
}

function scheduleTimelineProgress() {
  if (animationFrame !== null) return
  animationFrame = window.requestAnimationFrame(updateTimelineProgress)
}

function observeTimelineItems() {
  itemObserver?.disconnect()
  itemObserver = null
  if (!timelineRef.value) return

  const cards = [...timelineRef.value.querySelectorAll('[data-experience-card]')]
  if (prefersReducedMotion.value || !('IntersectionObserver' in window)) {
    cards.forEach((card) => {
      card.classList.add('revealed')
      card.parentElement?.querySelector('[data-experience-dot]')?.classList.add('is-visible')
    })
    return
  }

  itemObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return

      entry.target.classList.add('revealed')
      entry.target.parentElement?.querySelector('[data-experience-dot]')?.classList.add('is-visible')
      itemObserver?.unobserve(entry.target)
    })
  }, { threshold: 0.12, rootMargin: '0px 0px -50px 0px' })

  cards.forEach((card) => itemObserver.observe(card))
}

function handleMotionChange(event) {
  prefersReducedMotion.value = event.matches
  if (event.matches) {
    if (animationFrame !== null) {
      window.cancelAnimationFrame(animationFrame)
      animationFrame = null
    }
    if (timelineLineRef.value) timelineLineRef.value.style.transform = 'scaleY(1)'
  } else {
    observeTimelineItems()
    scheduleTimelineProgress()
  }
}

watch(
  () => portfolioStore.experiences.length,
  async () => {
    await nextTick()
    observeTimelineItems()
    scheduleTimelineProgress()
  },
  { flush: 'post' },
)

onMounted(() => {
  motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)')
  prefersReducedMotion.value = motionQuery.matches
  motionQuery.addEventListener('change', handleMotionChange)

  observeTimelineItems()
  scheduleTimelineProgress()
  window.addEventListener('scroll', scheduleTimelineProgress, { passive: true })
  window.addEventListener('resize', scheduleTimelineProgress, { passive: true })
})

onUnmounted(() => {
  itemObserver?.disconnect()
  motionQuery?.removeEventListener('change', handleMotionChange)
  window.removeEventListener('scroll', scheduleTimelineProgress)
  window.removeEventListener('resize', scheduleTimelineProgress)
  if (animationFrame !== null) window.cancelAnimationFrame(animationFrame)
})
</script>

<template>
  <section
    v-if="portfolioStore.experiences.length || portfolioStore.experiencesLoading || portfolioStore.experiencesError"
    id="experience"
    aria-labelledby="experience-title"
    class="experience-section py-20 border-b border-line/60"
  >
    <AppContainer class="space-y-12">
      <div class="space-y-2 max-w-xl">
        <span class="inline-block text-xs font-mono font-semibold uppercase tracking-wider text-accent">
          Career Path
        </span>
        <h2 id="experience-title" class="text-heading font-bold tracking-tight text-fg break-words">
          Work Experience
        </h2>
        <p class="text-sm sm:text-base text-muted">
          Previous roles, engineering responsibilities, and team collaborations.
        </p>
      </div>

      <div
        v-if="portfolioStore.experiencesLoading && !portfolioStore.experiences.length"
        class="w-full space-y-6"
        aria-label="Loading work experience"
        aria-busy="true"
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

      <ErrorState
        v-else-if="portfolioStore.experiencesError && !portfolioStore.experiences.length"
        :message="portfolioStore.experiencesError"
        @retry="portfolioStore.refresh()"
      />

      <div
        v-else
        ref="timelineRef"
        class="experience-timeline relative w-full min-w-0 pl-5 sm:pl-8"
        :class="{ 'experience-timeline--reduced-motion': prefersReducedMotion }"
      >
        <div class="experience-timeline-track" aria-hidden="true" />
        <div
          ref="timelineLineRef"
          class="experience-timeline-progress"
          aria-hidden="true"
        />

        <ol class="space-y-8">
          <li
            v-for="exp in portfolioStore.experiences"
            :key="exp.id"
            class="relative"
          >
            <span
              data-experience-dot
              class="experience-timeline-dot absolute -left-[19px] sm:-left-[27px] top-6"
              :class="{ 'is-visible': prefersReducedMotion }"
              aria-hidden="true"
            >
              <span class="experience-timeline-ring" />
            </span>

            <article
              data-experience-card
              class="experience-card reveal min-w-0 rounded-card border border-line bg-surface p-5 sm:p-6 shadow-card hover-card space-y-4"
              :class="{ 'experience-card--current': exp.is_current }"
            >
              <div class="space-y-2 border-b border-line pb-3">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                  <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                      <h3 class="min-w-0 text-lg font-semibold text-fg break-words">
                        {{ exp.role }}
                      </h3>
                      <span
                        v-if="exp.is_current"
                        class="inline-flex min-h-6 items-center rounded-full bg-accent-soft px-2.5 text-xs font-semibold text-accent"
                      >
                        Current
                      </span>
                    </div>
                  </div>

                  <div
                    v-if="exp.start_date ? formatPeriod(exp.start_date, exp.end_date, Boolean(exp.is_current)) : exp.period"
                    class="inline-flex max-w-full flex-wrap items-center self-start rounded-full border border-line bg-surface-2 px-3 py-1 text-xs font-mono font-medium text-muted sm:self-center"
                  >
                    <template v-if="exp.start_date">
                      <time :datetime="monthKey(exp.start_date)">{{ periodParts(exp).start }}</time>
                      <template v-if="periodParts(exp).end">
                        <span class="px-1">–</span>
                        <time
                          v-if="exp.end_date && !exp.is_current"
                          :datetime="monthKey(exp.end_date)"
                        >{{ periodParts(exp).end }}</time>
                        <span v-else>{{ periodParts(exp).end }}</span>
                      </template>
                      <span v-if="periodParts(exp).duration" class="ml-1 break-words">
                        · {{ periodParts(exp).duration }}
                      </span>
                    </template>
                    <span v-else>{{ exp.period }}</span>
                  </div>
                </div>

                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                  <div class="flex min-w-0 items-center gap-1.5 text-sm font-medium text-accent">
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                      <rect width="16" height="20" x="4" y="2" rx="2" />
                      <path d="M9 22v-4h6v4M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01" />
                    </svg>
                    <span class="min-w-0 break-words">{{ exp.organization }}</span>
                  </div>
                  <span
                    v-if="[exp.employment_type, exp.work_mode, exp.location].some(Boolean)"
                    class="min-w-0 break-words text-sm text-muted"
                  >
                    {{ [exp.employment_type, exp.work_mode, exp.location].filter(Boolean).join(' · ') }}
                  </span>
                </div>
              </div>

              <ul
                v-if="exp.highlights?.length"
                class="space-y-2 text-sm leading-relaxed text-muted"
              >
                <li
                  v-for="(highlight, index) in exp.highlights"
                  :key="`${exp.id}-highlight-${index}`"
                  class="relative line-clamp-4 pl-4 before:absolute before:left-0 before:top-[0.65em] before:h-1.5 before:w-1.5 before:rounded-full before:bg-accent"
                >
                  {{ highlight }}
                </li>
              </ul>

              <div v-else-if="exp.description" class="space-y-1">
                <p
                  class="text-sm leading-relaxed text-muted whitespace-pre-line"
                  :class="{ 'line-clamp-4': !expandedDescriptions[exp.id] }"
                >
                  {{ exp.description }}
                </p>
                <button
                  type="button"
                  class="min-h-11 rounded-control px-2 text-sm font-semibold text-accent hover:text-accent-hover focus-ring"
                  :aria-expanded="Boolean(expandedDescriptions[exp.id])"
                  @click="expandedDescriptions[exp.id] = !expandedDescriptions[exp.id]"
                >
                  {{ expandedDescriptions[exp.id] ? 'Show less' : 'Read more' }}
                </button>
              </div>

              <div v-if="exp.technologies?.length" class="flex flex-wrap items-center gap-1.5 pt-1">
                <span
                  v-for="technology in exp.technologies"
                  :key="technology"
                  class="max-w-full break-words rounded-control border border-line bg-surface-2 px-2 py-0.5 text-[11px] font-medium text-muted"
                >
                  {{ technology }}
                </span>
              </div>
            </article>
          </li>
        </ol>
      </div>
    </AppContainer>
  </section>
</template>

<style scoped>
.experience-timeline-track,
.experience-timeline-progress {
  position: absolute;
  top: 0.5rem;
  bottom: 0.5rem;
  left: 0.625rem;
  width: 2px;
  border-radius: 9999px;
}

.experience-timeline-track {
  background-color: var(--color-line);
}

.experience-timeline-progress {
  transform: scaleY(0);
  transform-origin: top;
  background-color: var(--color-accent);
  will-change: transform;
}

.experience-timeline-dot {
  z-index: 1;
  display: grid;
  width: 1rem;
  height: 1rem;
  place-items: center;
  border: 2px solid var(--color-line);
  border-radius: 9999px;
  background-color: var(--color-surface);
  transform: scale(0.85);
  transition: transform 200ms ease-out;
}

.experience-timeline-dot.is-visible {
  border-color: var(--color-accent);
  background-color: var(--color-accent);
  transform: scale(1);
}

.experience-timeline-ring {
  position: absolute;
  inset: -0.375rem;
  border: 2px solid var(--color-accent);
  border-radius: 9999px;
  opacity: 0;
  transition: opacity 200ms ease-out;
}

.experience-timeline-dot.is-visible .experience-timeline-ring {
  opacity: 0.3;
}

.experience-card--current {
  border-color: color-mix(in srgb, var(--color-accent) 60%, var(--color-line));
  border-width: 2px;
}

.experience-timeline--reduced-motion .experience-timeline-progress {
  transform: scaleY(1);
}

.experience-timeline--reduced-motion .experience-timeline-dot {
  border-color: var(--color-accent);
  background-color: var(--color-accent);
  transform: none;
}

.experience-timeline--reduced-motion .experience-timeline-ring {
  opacity: 0.3;
}

@media (min-width: 640px) {
  .experience-timeline-track,
  .experience-timeline-progress {
    left: 0.875rem;
  }
}

@media (max-width: 12rem) {
  .experience-section :deep(.app-container) {
    padding-inline: 0.5rem;
  }

  .experience-timeline {
    padding-left: 1rem;
  }

  .experience-timeline-dot {
    left: -1rem;
  }

  .experience-card {
    padding: 0.75rem;
  }
}

@media (prefers-reduced-motion: reduce) {
  .experience-timeline-progress {
    transform: scaleY(1) !important;
  }

  .experience-timeline-dot {
    border-color: var(--color-accent);
    background-color: var(--color-accent);
    transform: none;
  }

  .experience-timeline-ring {
    opacity: 0.3;
  }
}
</style>
