<!--
  SkeletonBlock.vue — Loading Placeholder
  ────────────────────────────────────────
  A shimmering placeholder rectangle shown while data is loading.
  The shimmer animation is defined in main.css.

  Props:
  - width: CSS width (default '100%')
  - height: CSS height (default '1rem')
  - rounded: whether to use fully rounded corners (for avatars/circles)

  Usage:
    <SkeletonBlock width="12rem" height="2rem" />
    <SkeletonBlock height="200px" />
    <SkeletonBlock width="3rem" height="3rem" rounded />

  Used by: all section components during loading states
-->

<script setup>
defineProps({
  /** CSS width value */
  width: { type: String, default: '100%' },
  /** CSS height value */
  height: { type: String, default: '1rem' },
  /** Use fully rounded corners (circle/pill shape) */
  rounded: { type: Boolean, default: false },
})
</script>

<template>
  <div
    class="skeleton"
    :class="{ 'rounded-full': rounded }"
    :style="{ width, height }"
    aria-hidden="true"
  />
</template>
