<!--
  AdminLayout.vue — Full Admin Panel Layout
  ──────────────────────────────────────────
  Wraps all admin pages with:
  - A fixed sidebar (AdminSidebar) for navigation
  - A top bar with mobile menu toggle + page title
  - A scrollable content area with the RouterView

  Used by: router (wraps /admin routes)
-->

<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { RouterView } from 'vue-router'
import AdminSidebar from '@/components/admin/AdminSidebar.vue'

const sidebarOpen = ref(false)
const route = useRoute()
</script>

<template>
  <div class="min-h-screen bg-bg text-fg flex">
    <!-- Sidebar -->
    <AdminSidebar v-model:open="sidebarOpen" />

    <!-- Main content area — offset by sidebar width on desktop -->
    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
      <!-- Top Bar -->
      <header class="sticky top-0 z-20 flex items-center gap-4 px-5 h-16 bg-surface border-b border-line shrink-0">
        <!-- Mobile: hamburger button -->
        <button
          class="lg:hidden p-2 rounded-control text-muted hover:text-fg hover:bg-surface-2 transition-colors"
          aria-label="Open sidebar"
          @click="sidebarOpen = true"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
          </svg>
        </button>

        <!-- Page title from route meta -->
        <h1 class="text-base font-semibold text-fg">
          {{ route.meta.title || 'Admin' }}
        </h1>
      </header>

      <!-- Page content -->
      <main class="flex-1 p-5 sm:p-7 overflow-y-auto">
        <RouterView />
      </main>
    </div>
  </div>
</template>
