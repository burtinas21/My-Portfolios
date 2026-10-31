<!--
  App.vue — Root Component
  ────────────────────────
  The top-level component that Vue mounts to the DOM.

  Responsibilities:
  1. Renders the current route via <RouterView>
  2. Wraps routes in a <Transition> for page fade animations
  3. Renders ToastHost (so toasts appear on every page)
  4. Provides the skip-to-content link for keyboard users

  This component is intentionally minimal. Layout logic lives in
  PublicLayout.vue and AdminLayout.vue.
-->

<script setup>
import { RouterView } from 'vue-router'
import ToastHost from '@/components/ui/ToastHost.vue'
</script>

<template>
  <!-- Skip-to-content link: invisible until focused, for keyboard users -->
  <a href="#main-content" class="skip-link">
    Skip to content
  </a>

  <!-- Page transition: fades between routes -->
  <RouterView v-slot="{ Component, route }">
    <Transition name="page" mode="out-in">
      <component :is="Component" :key="route.path" />
    </Transition>
  </RouterView>

  <!-- Toast notifications: always visible on top of everything -->
  <ToastHost />
</template>