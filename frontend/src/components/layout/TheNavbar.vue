<!--
  TheNavbar.vue — Main Site Navigation Bar
  ───────────────────────────────────────────
  A sticky, responsive navigation bar with:
  - Initials / Logo linking to top
  - Desktop nav links with active section highlight (Scroll Spy)
  - Reading scroll-progress bar along the bottom edge
  - Theme toggle (system / light / dark)
  - Quick link to Admin Login
  - Mobile hamburger menu drawer with smooth toggle
  - Accessible keyboard navigation and ARIA attributes
-->

<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { usePortfolioStore } from '@/stores/portfolio.js'
import { useAuthStore } from '@/stores/auth.js'
import AppContainer from '@/components/ui/AppContainer.vue'
import ThemeToggle from '@/components/layout/ThemeToggle.vue'
import { getVisibleNavigationSections } from '@/utils/navigationSections.js'
import { getCvDownloadUrl } from '@/api/cv.js'

const portfolioStore = usePortfolioStore()
const authStore = useAuthStore()
const route = useRoute()

// Track active section for nav link highlight
const activeSection = ref('hero')
// Mobile menu open/closed state
const isMobileMenuOpen = ref(false)
// Reading scroll progress percentage (0 - 100)
const scrollProgress = ref(0)

// Navigation items definition
const navItems = computed(() => getVisibleNavigationSections(
  portfolioStore.certifications,
  portfolioStore.experiences.length > 0
    || portfolioStore.experiencesLoading
    || Boolean(portfolioStore.experiencesError),
).map((section) => ({ label: section.label, href: `#${section.id}` })))

/**
 * Handle scroll events:
 * 1. Calculate reading scroll percentage
 * 2. Update active section based on current scroll position
 */
function handleScroll() {
  const scrollTop = window.scrollY || document.documentElement.scrollTop
  const docHeight = document.documentElement.scrollHeight - window.innerHeight
  scrollProgress.value = docHeight > 0 ? Math.min(100, Math.max(0, (scrollTop / docHeight) * 100)) : 0

  // Determine which section is currently visible
  for (const { id } of [...navItems.value].reverse().map((item) => ({ id: item.href.slice(1) }))) {
    const el = document.getElementById(id)
    if (el) {
      const rect = el.getBoundingClientRect()
      // Section is active if its top is near or above the navbar line
      if (rect.top <= 160) {
        activeSection.value = id
        break
      }
    }
  }
}

watch(navItems, handleScroll)

/**
 * Close mobile menu when an anchor link is clicked
 */
function closeMobileMenu() {
  isMobileMenuOpen.value = false
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true })
  handleScroll()
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <header
    class="sticky top-0 z-40 border-b border-line bg-surface/80 backdrop-blur-md transition-colors"
  >
    <!-- Reading scroll progress indicator line -->
    <div
      class="absolute top-0 left-0 h-[2px] w-full origin-left bg-accent transition-transform duration-75 ease-out"
      :style="{ transform: `scaleX(${scrollProgress / 100})` }"
      aria-hidden="true"
    />

    <AppContainer>
      <nav
        class="flex items-center justify-between h-16"
        aria-label="Main Navigation"
      >
      <!-- Logo / Name Initials -->
      <a
        href="#hero"
        class="group flex items-center gap-2.5 font-bold text-fg focus-ring rounded-control p-1"
        @click="closeMobileMenu"
      >
        <span
          class="flex items-center justify-center w-9 h-9 rounded-control bg-accent text-on-accent font-mono text-sm tracking-wider font-extrabold shadow-sm hover-skill-icon"
        >
          {{ portfolioStore.profile?.initials || 'DEV' }}
        </span>
        <span class="font-semibold text-base hidden sm:inline-block tracking-tight hover-nav-link">
          Burtinas
        </span>
      </a>

      <!-- Desktop Nav Links -->
      <div class="hidden lg:flex items-center gap-1">
        <a
          v-for="item in navItems"
          :key="item.href"
          :href="item.href"
          :class="[
            'px-3.5 py-1.5 rounded-control text-sm font-medium hover-nav-link focus-ring relative',
            activeSection === item.href.substring(1)
              ? 'text-accent font-semibold'
              : 'text-muted',
          ]"
        >
          {{ item.label }}
          <!-- Active indicator dot -->
          <span
            v-if="activeSection === item.href.substring(1)"
            class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-accent"
            aria-hidden="true"
          />
        </a>
      </div>

      <!-- Right controls: Theme Toggle + Admin Link + Mobile Menu Toggle -->
      <div class="flex items-center gap-2 sm:gap-3">
        <!-- Theme Switcher -->
        <ThemeToggle />

        <!-- Public CV link; open the PDF inline in a new tab -->
        <a
          v-if="portfolioStore.cv.available"
          :href="getCvDownloadUrl(true)"
          target="_blank"
          rel="noopener noreferrer"
          class="icon-button"
          aria-label="View CV / Resume (PDF)"
          title="View CV / Resume"
        >
          <svg
            class="w-5 h-5"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
          >
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
            <path d="M14 2v6h6M8 13h8M8 17h8" />
          </svg>
        </a>

        <!-- Admin Portal Icon Link (only shown when authenticated) -->
        <RouterLink
          v-if="authStore.isAuthenticated"
          to="/admin"
          title="Admin Panel"
          class="icon-button"
          aria-label="Admin Dashboard"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="18" height="18" x="3" y="3" rx="2" />
            <path d="M7 7h.01M7 12h.01M7 17h.01M12 7h5M12 12h5M12 17h5" />
          </svg>
        </RouterLink>

        <!-- Mobile Menu Hamburger Button -->
        <button
          type="button"
          class="icon-button mobile-menu-toggle lg:hidden"
          :aria-expanded="isMobileMenuOpen"
          aria-controls="mobile-menu"
          aria-label="Toggle mobile menu"
          @click="isMobileMenuOpen = !isMobileMenuOpen"
        >
          <!-- Hamburger icon when closed -->
          <svg
            v-if="!isMobileMenuOpen"
            class="w-5 h-5"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
          >
            <path d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <!-- Close X icon when open -->
          <svg
            v-else
            class="w-5 h-5"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
          >
            <path d="M18 6L6 18M6 6l12 12" />
          </svg>
        </button>
      </div>
      </nav>
    </AppContainer>

    <!-- Mobile Dropdown Menu -->
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0 -translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-2"
    >
      <div
        v-if="isMobileMenuOpen"
        id="mobile-menu"
        class="lg:hidden border-b border-line bg-surface/95 backdrop-blur-md px-4 py-4 space-y-1.5 shadow-overlay"
      >
        <a
          v-for="item in navItems"
          :key="item.href"
          :href="item.href"
          :class="[
            'block px-3.5 py-2.5 rounded-control text-sm font-medium hover-nav-link focus-ring',
            activeSection === item.href.substring(1)
              ? 'bg-accent/10 text-accent font-semibold'
              : 'text-muted',
          ]"
          @click="closeMobileMenu"
        >
          {{ item.label }}
        </a>

        <div class="pt-2 border-t border-line flex items-center justify-between">
          <a
            href="#contact"
            class="w-full text-center px-4 py-2 rounded-control text-sm font-semibold bg-accent text-on-accent hover-button-primary focus-ring"
            @click="closeMobileMenu"
          >
            Get In Touch
          </a>
        </div>
      </div>
    </Transition>
  </header>
</template>

<style scoped>
@reference "@/assets/main.css";

/* Icon button - consistent 44x44px size, 20px icons, same hover/focus styles */
.icon-button {
  @apply flex items-center justify-center
         text-muted
         rounded-control
         focus-ring;
  width: 2.75rem;  /* 44px */
  height: 2.75rem; /* 44px */
  display: grid;
  place-items: center;
  transition: color var(--transition-hover),
              border-color var(--transition-hover),
              background-color var(--transition-hover);
}

@media (min-width: 64rem) {
  .mobile-menu-toggle {
    display: none;
  }
}

@media (hover: hover) {
  .icon-button:hover {
    @apply text-accent bg-accent-soft;
  }
}
</style>
