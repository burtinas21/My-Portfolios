<!--
  AppContainer.vue — Shared Site-Wide Container
  ──────────────────────────────────────────────
  Provides consistent horizontal alignment across all sections.
  Replaces ad-hoc width and padding classes throughout the site.

  Usage:
    <AppContainer>
      <YourContent />
    </AppContainer>

  Spacing: Responsive padding that scales from 1rem (mobile) to 2rem (desktop)
  Width: Constrained to 72rem (1152px) max-width, centered with mx-auto
-->

<script setup>
// No props or logic needed — this is a pure layout component
</script>

<template>
  <div class="app-container">
    <slot />
  </div>
</template>

<style scoped>
.app-container {
  width: 100%;
  max-width: 72rem; /* 1152px */
  margin-inline: auto;
  padding-inline: 1rem;
}

@media (min-width: 640px) {
  .app-container {
    padding-inline: 1.5rem;
  }
}

@media (min-width: 1024px) {
  .app-container {
    padding-inline: 2rem;
  }
}
</style>
