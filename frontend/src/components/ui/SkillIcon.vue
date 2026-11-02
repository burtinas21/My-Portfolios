<!--
  SkillIcon.vue — Technology logo icon chip
  ──────────────────────────────────────────
  Renders an Iconify icon inside a white rounded chip so dark-colored logos
  (GitHub, Next.js, etc.) remain visible in dark mode. Falls back to the
  first letter of the skill name when the icon prop is empty or fails to load.

  Props:
    icon  — Iconify icon name  (e.g. "logos:laravel")
    name  — Skill display name  (e.g. "Laravel") — always shown as text next to chip
    size  — Icon pixel size (default 28)

  Used by:
    - components/sections/SkillsSection.vue  (public skill tiles)
    - views/admin/SkillsView.vue             (admin skill list + form preview)
-->

<script setup>
import { ref, watch } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  /** Iconify icon identifier, e.g. "logos:laravel". Empty string = show fallback. */
  icon: { type: String, default: '' },
  /** Human-readable skill name. First letter used as fallback. */
  name: { type: String, required: true },
  /** Size of the icon in pixels. Chip dimensions scale proportionally. */
  size: { type: Number, default: 28 },
})

// Track load failure so we can swap to letter fallback
const failed = ref(false)

// Reset failed state whenever the icon prop changes
watch(() => props.icon, () => { failed.value = false })

/**
 * Iconify fires an 'error' event when an icon name is unknown / network fails.
 * We catch that and show the letter fallback instead.
 */
function onIconError() {
  failed.value = true
}

/** First uppercase letter of the skill name, used as text fallback. */
function initials(name) {
  return (name || '?').charAt(0).toUpperCase()
}
</script>

<template>
  <!--
    The chip wrapper is always white so dark SVG icons (GitHub, Next.js, etc.)
    read well in dark mode. The subtle ring separates it from both light and dark backgrounds.
    Padding and border-radius are rem-based so they scale with browser font-size at 200% zoom.
  -->
  <span
    class="skill-icon-chip hover-skill-icon"
    :style="{ width: `${size + 16}px`, height: `${size + 16}px` }"
    :title="name"
  >
    <!-- Iconify icon — aria-hidden because the skill name text is always present nearby -->
    <Icon
      v-if="icon && !failed"
      :icon="icon"
      :width="size"
      :height="size"
      aria-hidden="true"
      @error="onIconError"
    />

    <!-- Letter fallback when icon is empty or fails to load -->
    <span
      v-else
      class="skill-icon-fallback"
      :style="{ fontSize: `${Math.max(10, size * 0.5)}px` }"
      aria-hidden="true"
    >
      {{ initials(name) }}
    </span>
  </span>
</template>

<style scoped>
@reference "@/assets/main.css";

/*
 * White chip — keeps logos readable on any background in dark mode.
 * Uses rem-based border-radius so it stays round at 200% browser zoom.
 */
.skill-icon-chip {
  @apply inline-flex items-center justify-center
         rounded-lg bg-white
         ring-1 ring-line
         shrink-0
         overflow-hidden;
  /* Use padding fraction so scaling with size prop still looks balanced */
  padding: 0.25rem;
}

/* Letter fallback inherits accent colour from design tokens */
.skill-icon-fallback {
  @apply font-bold text-accent leading-none;
}
</style>
