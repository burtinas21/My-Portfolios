<!--
  CvDownloadButton.vue — CV Download Button Component
  ───────────────────────────────────────────────────
  A real <a href download> link (works without JavaScript) with
  consistent button styling for downloading the CV.

  Props:
  - variant: 'primary' (accent background), 'outline' (bordered), or 'inline' (text link)
  - available: whether the CV is available for download

  Features:
  - Real anchor tag (accessible without JS)
  - Min height 44px (WCAG touch target)
  - Shared hover and focus styles
  - Download icon from Iconify (lucide:download)
  - Renders nothing when unavailable

  Used by: HeroSection.vue, CvCard.vue
-->

<script setup>
import { computed } from 'vue'
import { getCvDownloadUrl } from '@/api/cv.js'

const props = defineProps({
  /** Visual style: 'primary' | 'outline' | 'inline' */
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'outline', 'inline'].includes(v),
  },
  /** Whether CV is available */
  available: {
    type: Boolean,
    default: false,
  },
  /** Size in KB or bytes */
  size: {
    type: Number,
    default: null,
  },
})

const downloadUrl = computed(() => getCvDownloadUrl(false))

const formattedSize = computed(() => {
  if (!props.size) return ''
  const kb = props.size / 1024
  return kb >= 1024 ? `${(kb / 1024).toFixed(0)} MB` : `${Math.round(kb)} KB`
})

const ariaLabel = computed(() => {
  if (formattedSize.value) {
    return `Download CV (PDF, ${formattedSize.value})`
  }
  return 'Download CV (PDF)'
})

const variantClasses = computed(() => {
  if (props.variant === 'primary') {
    return 'bg-accent text-on-accent hover-button-primary shadow-card'
  }
  if (props.variant === 'inline') {
    return 'min-h-[44px] px-0 py-0 rounded-none text-accent hover-link underline-offset-4 hover:underline'
  }
  return 'border border-line bg-surface text-fg hover-button-outlined'
})
</script>

<template>
  <a
    v-if="available"
    :href="downloadUrl"
    download
    :aria-label="ariaLabel"
    :class="[
      'inline-flex items-center justify-center gap-2 px-6 py-3 rounded-control text-sm font-semibold',
      'min-h-[44px] transition-all duration-150 active:scale-95 focus-ring',
      variantClasses,
    ]"
  >
    <!-- Download icon (lucide:download) -->
    <svg
      class="w-4 h-4"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
      <polyline points="7 10 12 15 17 10" />
      <line x1="12" y1="15" x2="12" y2="3" />
    </svg>
    <span>Download CV</span>
  </a>
</template>
