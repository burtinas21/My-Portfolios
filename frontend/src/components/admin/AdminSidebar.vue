<!--
  AdminSidebar.vue — Admin Panel Navigation Sidebar
  ──────────────────────────────────────────────────
  Renders the vertical navigation for the admin panel.
  - Desktop: fixed sidebar
  - Mobile: slides in as a drawer (controlled by `open` prop)

  Props:
  - open: controls mobile drawer visibility

  Events:
  - update:open: emits false when the drawer should close

  Used by: AdminLayout.vue
-->

<script setup>
import { RouterLink, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { useRouter } from 'vue-router'
import { useToast } from '@/composables/useToast.js'

defineProps({
  open: { type: Boolean, default: false },
})

const emit = defineEmits(['update:open'])
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const navItems = [
  {
    name: 'Dashboard',
    to: { name: 'admin-dashboard' },
    icon: `<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>`,
  },
  {
    name: 'Profile',
    to: { name: 'admin-profile' },
    icon: `<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>`,
  },
  {
    name: 'Projects',
    to: { name: 'admin-projects' },
    icon: `<rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>`,
  },
  {
    name: 'Skills',
    to: { name: 'admin-skills' },
    icon: `<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>`,
  },
  {
    name: 'Experience',
    to: { name: 'admin-experience' },
    icon: `<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/>`,
  },
  {
    name: 'Socials',
    to: { name: 'admin-socials' },
    icon: `<path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>`,
  },
  {
    name: 'Messages',
    to: { name: 'admin-messages' },
    icon: `<path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>`,
  },
]

function isActive(item) {
  return route.name === item.to.name
}

function close() {
  emit('update:open', false)
}

async function handleLogout() {
  await authStore.logout()
  toast.success('Logged out successfully.')
  router.replace({ name: 'admin-login' })
}
</script>

<template>
  <!-- Mobile overlay backdrop -->
  <Transition name="backdrop">
    <div
      v-if="open"
      class="fixed inset-0 z-30 bg-black/50 backdrop-blur-sm lg:hidden"
      @click="close"
    />
  </Transition>

  <!-- Sidebar panel -->
  <aside
    :class="[
      'fixed top-0 left-0 z-40 h-full w-64 flex flex-col',
      'bg-surface border-r border-line',
      'transition-transform duration-300 ease-in-out',
      // Desktop: always visible | Mobile: slide in/out
      open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
    ]"
  >
    <!-- Logo / Brand -->
    <div class="flex items-center gap-3 px-5 py-5 border-b border-line shrink-0">
      <div class="w-9 h-9 rounded-control bg-accent flex items-center justify-center text-on-accent font-bold text-sm shrink-0">
        A
      </div>
      <div class="overflow-hidden">
        <p class="text-sm font-semibold text-fg leading-tight truncate">Admin Panel</p>
        <p class="text-xs text-muted truncate">{{ authStore.user?.name || 'Loading...' }}</p>
      </div>
      <!-- Close button (mobile only) -->
      <button
        class="ml-auto lg:hidden p-1.5 rounded-control text-muted hover-icon focus-ring"
        aria-label="Close sidebar"
        @click="close"
      >
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <path d="M18 6 6 18M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-4 px-3" aria-label="Admin navigation">
      <ul class="space-y-0.5">
        <li v-for="item in navItems" :key="item.name">
          <RouterLink
            :to="item.to"
            class="flex items-center gap-3 px-3 py-2.5 rounded-control text-sm font-medium hover-table-row focus-ring"
            :class="
              isActive(item)
                ? 'bg-accent-soft text-accent'
                : 'text-muted'
            "
            @click="close"
          >
            <svg
              class="w-4.5 h-4.5 shrink-0"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.75"
              stroke-linecap="round"
              stroke-linejoin="round"
              v-html="item.icon"
            />
            {{ item.name }}
          </RouterLink>
        </li>
      </ul>
    </nav>

    <!-- Bottom: View Site + Logout -->
    <div class="px-3 py-4 border-t border-line space-y-1 shrink-0">
      <a
        href="/"
        target="_blank"
        rel="noopener"
        class="flex items-center gap-3 px-3 py-2.5 rounded-control text-sm font-medium text-muted hover-table-row focus-ring"
      >
        <svg class="w-4.5 h-4.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/>
          <polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
        </svg>
        View Site
      </a>
      <button
        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-control text-sm font-medium text-muted hover:text-danger hover:bg-danger/10 focus-ring"
        style="transition: color var(--transition-hover), background-color var(--transition-hover);"
        @click="handleLogout"
      >
        <svg class="w-4.5 h-4.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
          <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        Log Out
      </button>
    </div>
  </aside>
</template>
