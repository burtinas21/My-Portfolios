<!--
  ProjectModal.vue — Detailed Project Preview Dialog
  ──────────────────────────────────────────────────
  Accessible modal popup displaying complete project details:
  - High-res image preview
  - Category, year, and metadata
  - Full project summary & in-depth case study / detail description
  - Complete technology stack list
  - Live demo link action
-->

<script setup>
import { computed } from 'vue'
import { imageUrl } from '@/utils/url.js'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  project: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['update:open'])

const fullImageUrl = computed(() => imageUrl(props.project?.image))

function close() {
  emit('update:open', false)
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="project?.title || 'Project Details'"
    max-width="max-w-2xl"
    @update:open="emit('update:open', $event)"
  >
    <div v-if="project" class="space-y-6">
      <!-- Media Preview -->
      <div
        v-if="fullImageUrl"
        class="w-full h-64 sm:h-72 rounded-control overflow-hidden border border-line bg-surface-2"
      >
        <img
          :src="fullImageUrl"
          :alt="project.title"
          class="w-full h-full object-cover"
        />
      </div>

      <!-- Tag & Year Row -->
      <div class="flex flex-wrap items-center justify-between gap-3 pb-2 border-b border-line">
        <div class="flex items-center gap-2">
          <span class="px-3 py-1 rounded-full text-xs font-semibold bg-accent-soft text-accent">
            {{ project.tag }}
          </span>
          <span class="px-2.5 py-1 rounded-control text-xs font-mono font-medium bg-surface-2 text-muted">
            {{ project.year }}
          </span>
        </div>

        <div v-if="project.url">
          <a
            :href="project.url"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-accent hover:underline"
          >
            <span>Visit Live Project</span>
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
          </a>
        </div>
      </div>

      <!-- Overview Summary -->
      <div class="space-y-2">
        <h4 class="text-xs font-semibold uppercase tracking-wider text-muted">
          Overview
        </h4>
        <p class="text-base text-fg font-medium leading-relaxed">
          {{ project.summary }}
        </p>
      </div>

      <!-- In-Depth Details (if present) -->
      <div v-if="project.detail" class="space-y-2">
        <h4 class="text-xs font-semibold uppercase tracking-wider text-muted">
          Case Study & Implementation
        </h4>
        <p class="text-sm text-muted leading-relaxed whitespace-pre-line">
          {{ project.detail }}
        </p>
      </div>

      <!-- Full Tech Stack Tags -->
      <div v-if="project.stack && project.stack.length" class="space-y-2 pt-2">
        <h4 class="text-xs font-semibold uppercase tracking-wider text-muted">
          Technologies Used
        </h4>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="tech in project.stack"
            :key="tech"
            class="px-2.5 py-1 rounded-control text-xs font-medium bg-surface-2 border border-line text-fg"
          >
            {{ tech }}
          </span>
        </div>
      </div>
    </div>

    <!-- Modal Footer Actions -->
    <template #footer>
      <BaseButton variant="secondary" size="sm" @click="close">
        Close
      </BaseButton>

      <a
        v-if="project?.url"
        :href="project.url"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex items-center justify-center font-medium rounded-control transition-all duration-150 px-4 py-2 text-sm gap-2 bg-accent text-on-accent hover:bg-accent-hover"
      >
        <span>Open Project</span>
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
        </svg>
      </a>
    </template>
  </BaseModal>
</template>
