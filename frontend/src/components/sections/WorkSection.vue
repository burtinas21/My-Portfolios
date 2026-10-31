<!--
  WorkSection.vue — Featured Projects Section
  ───────────────────────────────────────────
  Displays portfolio projects with:
  - Eyebrow badge and fluid section heading
  - Tag filter buttons with item count badges ("All", "Web App", "SaaS", etc.)
  - Responsive project cards grid using ProjectCard
  - Interactive project detail modal dialog via ProjectModal
  - Loading skeleton placeholders & EmptyState handling
-->

<script setup>
import { ref, computed } from 'vue'
import { usePortfolioStore } from '@/stores/portfolio.js'
import ProjectCard from '@/components/sections/ProjectCard.vue'
import ProjectModal from '@/components/sections/ProjectModal.vue'
import SkeletonBlock from '@/components/ui/SkeletonBlock.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import ErrorState from '@/components/ui/ErrorState.vue'

const portfolioStore = usePortfolioStore()

// Selected filter tag ('all' or specific tag like 'Web App')
const activeFilter = ref('all')

// Selected project for the details modal
const selectedProject = ref(null)
const isModalOpen = ref(false)

/**
 * Filter projects by selected tag
 */
const filteredProjects = computed(() => {
  if (activeFilter.value === 'all') {
    return portfolioStore.projects
  }
  return portfolioStore.projects.filter(
    (project) => project.tag?.toLowerCase() === activeFilter.value.toLowerCase(),
  )
})

/**
 * Count projects for a specific tag
 */
function getTagCount(tag) {
  if (tag === 'all') return portfolioStore.projects.length
  return portfolioStore.projects.filter(
    (p) => p.tag?.toLowerCase() === tag.toLowerCase(),
  ).length
}

/**
 * Open project details modal
 */
function openDetails(project) {
  selectedProject.value = project
  isModalOpen.value = true
}
</script>

<template>
  <section
    id="work"
    aria-labelledby="work-title"
    class="py-20 border-b border-line/60"
  >
    <div class="container-main space-y-12">
      <!-- Section Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div class="space-y-2 max-w-xl">
          <span class="inline-block text-xs font-mono font-semibold uppercase tracking-wider text-accent">
            Selected Work
          </span>
          <h2 id="work-title" class="text-heading font-bold tracking-tight text-fg">
            Featured Projects
          </h2>
          <p class="text-sm sm:text-base text-muted">
            Production-ready web applications, SaaS platforms, and enterprise solutions.
          </p>
        </div>

        <!-- Filter Tags -->
        <div
          v-if="portfolioStore.projects.length > 0"
          class="flex flex-wrap items-center gap-1.5 self-start md:self-auto"
          role="tablist"
          aria-label="Filter projects by category"
        >
          <!-- 'All' Filter Pill -->
          <button
            type="button"
            role="tab"
            :aria-selected="activeFilter === 'all'"
            :class="[
              'px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-150 flex items-center gap-1.5',
              activeFilter === 'all'
                ? 'bg-accent text-on-accent shadow-sm'
                : 'bg-surface border border-line text-muted hover:text-fg hover:bg-surface-2',
            ]"
            @click="activeFilter = 'all'"
          >
            <span>All</span>
            <span
              :class="[
                'text-[10px] px-1.5 py-0.2 rounded-full font-mono',
                activeFilter === 'all' ? 'bg-black/20 text-on-accent' : 'bg-surface-2 text-muted',
              ]"
            >
              {{ getTagCount('all') }}
            </span>
          </button>

          <!-- Dynamic Tags from Project Store -->
          <button
            v-for="tag in portfolioStore.projectTags"
            :key="tag"
            type="button"
            role="tab"
            :aria-selected="activeFilter === tag"
            :class="[
              'px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-150 flex items-center gap-1.5',
              activeFilter === tag
                ? 'bg-accent text-on-accent shadow-sm'
                : 'bg-surface border border-line text-muted hover:text-fg hover:bg-surface-2',
            ]"
            @click="activeFilter = tag"
          >
            <span>{{ tag }}</span>
            <span
              :class="[
                'text-[10px] px-1.5 py-0.2 rounded-full font-mono',
                activeFilter === tag ? 'bg-black/20 text-on-accent' : 'bg-surface-2 text-muted',
              ]"
            >
              {{ getTagCount(tag) }}
            </span>
          </button>
        </div>
      </div>

      <!-- Loading Skeleton Grid -->
      <div
        v-if="portfolioStore.projectsLoading && !portfolioStore.projects.length"
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
      >
        <div v-for="i in 3" :key="i" class="rounded-card border border-line p-4 space-y-4">
          <SkeletonBlock height="12rem" class="rounded-control" />
          <SkeletonBlock height="1.5rem" width="70%" />
          <SkeletonBlock height="3rem" width="100%" />
          <div class="flex gap-2">
            <SkeletonBlock height="1.25rem" width="4rem" />
            <SkeletonBlock height="1.25rem" width="4rem" />
          </div>
        </div>
      </div>

      <!-- Error State with Retry -->
      <div v-else-if="portfolioStore.projectsError && !portfolioStore.projects.length">
        <ErrorState
          :message="portfolioStore.projectsError"
          @retry="portfolioStore.refresh()"
        />
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredProjects.length === 0">
        <EmptyState
          title="No projects found"
          :description="activeFilter === 'all' ? 'No projects have been published yet.' : `No projects found under '${activeFilter}'.`"
        />
      </div>

      <!-- Projects Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <ProjectCard
          v-for="project in filteredProjects"
          :key="project.id"
          :project="project"
          @view-details="openDetails"
        />
      </div>
    </div>

    <!-- Project Details Modal -->
    <ProjectModal
      v-model:open="isModalOpen"
      :project="selectedProject"
    />
  </section>
</template>
