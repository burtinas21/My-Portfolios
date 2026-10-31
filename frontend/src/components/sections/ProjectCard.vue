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
import { imageUrl } from '@/utils/url.js'

const props = defineProps({
  project: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['view-details'])

const fullImageUrl = computed(() => imageUrl(props.project.image))

// Display max 4 stack items directly on the card for clean layout
const visibleStack = computed(() => (props.project.stack || []).slice(0, 4))
const remainingCount = computed(() => Math.max(0, (props.project.stack || []).length - 4))
</script>

<template>
  <article
    class="group flex flex-col rounded-card border border-line bg-surface shadow-card hover:shadow-overlay transition-all duration-300 hover:-translate-y-1 overflow-hidden"
  >
    <!-- Card Media / Preview Header -->
    <div class="relative h-48 w-full bg-surface-2 overflow-hidden border-b border-line flex items-center justify-center">
      <!-- Real uploaded project image -->
      <img
        v-if="fullImageUrl"
        :src="fullImageUrl"
        :alt="project.title"
        loading="lazy"
        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
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
      <div class="pt-3 border-t border-line flex items-center justify-between gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 text-xs font-semibold text-accent hover:text-accent-hover transition-colors py-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent rounded-control"
          @click="emit('view-details', project)"
        >
          <span>View Details</span>
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14m-7-7 7 7-7 7" />
          </svg>
        </button>

        <a
          v-if="project.url"
          :href="project.url"
          target="_blank"
          rel="noopener noreferrer"
          title="Open live project"
          class="inline-flex items-center gap-1 text-xs text-muted hover:text-fg transition-colors px-2 py-1 rounded-control hover:bg-surface-2"
        >
          <span>Live Demo</span>
          <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
            <polyline points="15 3 21 3 21 9" />
            <line x1="10" y1="14" x2="21" y2="3" />
          </svg>
        </a>
      </div>
    </div>
  </article>
</template>
