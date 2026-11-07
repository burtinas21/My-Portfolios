<!--
  ProjectCard.vue — Project Card Component
  ──────────────────────────────────────────
  Renders an individual portfolio project with:
  - Project image or abstract generative fallback banner
  - Category tag & creation year badges
  - Project title & summary
  - Tech stack tags
  - Interactive "View Details" trigger (opens modal)
  - External live link (if URL is present)
-->

<script setup>
import { computed } from 'vue'
import { ExternalLink, Github } from 'lucide-vue-next'
import { assetUrl } from '@/utils/url.js'

const props = defineProps({
  project: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['view-details'])

const fullImageUrl = computed(() => assetUrl(props.project.image))

// Display max 4 stack items directly on the card for clean layout
const visibleStack = computed(() => (props.project.stack || []).slice(0, 4))
const remainingCount = computed(() => Math.max(0, (props.project.stack || []).length - 4))
</script>

<template>
  <article
    class="group flex flex-col rounded-card border border-line bg-surface shadow-card hover-card overflow-hidden"
  >
    <!-- Card Media / Preview Header -->
    <div class="relative h-48 w-full bg-surface-2 overflow-hidden border-b border-line flex items-center justify-center hover-image-overlay">
      <!-- Real uploaded project image -->
      <img
        v-if="fullImageUrl"
        :src="fullImageUrl"
        :alt="project.title"
        width="800"
        height="450"
        loading="lazy"
        decoding="async"
        class="w-full h-full object-cover hover-image"
      />

      <!-- Tech Fallback Banner when image is null -->
      <div
        v-else
        class="w-full h-full bg-gradient-to-br from-surface-2 via-surface to-accent/15 flex flex-col items-center justify-center p-6 text-center select-none"
      >
        <div class="w-12 h-12 rounded-control bg-accent/10 border border-accent/20 text-accent flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
          <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="16 18 22 12 16 6" />
            <polyline points="8 6 2 12 8 18" />
          </svg>
        </div>
        <span class="text-xs font-semibold text-muted uppercase tracking-wider">
          {{ project.tag || 'Project' }}
        </span>
      </div>

      <!-- Top Overlay Badges: Tag and Year -->
      <div class="absolute top-3 left-3 flex items-center gap-1.5">
        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-surface/90 backdrop-blur-md border border-line text-fg shadow-sm">
          {{ project.tag }}
        </span>
      </div>

      <div class="absolute top-3 right-3">
        <span class="px-2 py-0.5 rounded-control text-xs font-mono font-medium bg-surface/90 backdrop-blur-md border border-line text-muted shadow-sm">
          {{ project.year }}
        </span>
      </div>
    </div>

    <!-- Card Body Content -->
    <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
      <div class="space-y-2">
        <h3 class="text-lg font-bold text-fg group-hover:text-accent transition-colors line-clamp-1">
          {{ project.title }}
        </h3>

        <p class="text-sm text-muted line-clamp-2 leading-relaxed">
          {{ project.summary }}
        </p>
      </div>

      <!-- Stack Badges -->
      <div class="flex flex-wrap items-center gap-1.5 pt-1">
        <span
          v-for="tech in visibleStack"
          :key="tech"
          class="px-2 py-0.5 rounded-control text-[11px] font-medium bg-surface-2 border border-line text-muted"
        >
          {{ tech }}
        </span>
        <span
          v-if="remainingCount > 0"
          class="px-1.5 py-0.5 rounded-control text-[11px] font-mono text-muted bg-surface-2"
        >
          +{{ remainingCount }}
        </span>
      </div>

      <!-- Action Footer -->
      <div class="pt-3 border-t border-line flex flex-wrap items-center justify-between gap-2">
        <button
          type="button"
          class="inline-flex min-h-11 items-center gap-1.5 rounded-control px-2 text-xs font-semibold text-accent hover:text-accent-hover transition-colors focus-ring"
          @click="emit('view-details', project)"
        >
          <span>View Details</span>
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14m-7-7 7 7-7 7" />
          </svg>
        </button>

        <div class="flex flex-wrap items-center gap-1">
          <a
            v-if="project.github_url"
            :href="project.github_url"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="View GitHub repository"
            class="inline-flex min-h-11 items-center gap-1.5 rounded-control px-2 text-xs font-medium text-muted hover-link focus-ring"
          >
            <Github class="h-4 w-4" aria-hidden="true" />
            <span>GitHub</span>
          </a>
          <a
            v-if="project.url"
            :href="project.url"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="Open live demo"
            class="inline-flex min-h-11 items-center gap-1.5 rounded-control px-2 text-xs font-medium text-muted hover-link focus-ring"
          >
            <ExternalLink class="h-3.5 w-3.5" aria-hidden="true" />
            <span>Live Demo</span>
          </a>
        </div>
      </div>
    </div>
  </article>
</template>
