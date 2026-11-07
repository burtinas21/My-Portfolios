<!--
  TheFooter.vue — Public Site Footer
  ───────────────────────────────────
  Slim, balanced, professional footer layout with:
  - Logo chip + name + role line (split headline on "|")
  - Navigation links (same as navbar, smooth scroll)
  - Social icons (using shared SocialLinks component)
  - Copyright + admin lock icon + "Back to top" button
  - Responsive layouts: desktop (3 cols), medium (2 rows), mobile (stacked)
  - Skeleton loading for brand and icons while data loads
-->

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { Icon } from '@iconify/vue'
import { usePortfolioStore } from '@/stores/portfolio.js'
import { useAuthStore } from '@/stores/auth.js'
import AppContainer from '@/components/ui/AppContainer.vue'
import SocialLinks from '@/components/ui/SocialLinks.vue'
import { getVisibleNavigationSections } from '@/utils/navigationSections.js'

/** Control admin lock icon visibility for visitors (true = always show, false = only when logged in) */
const SHOW_ADMIN_LINK_TO_VISITORS = true

const portfolioStore = usePortfolioStore()
const authStore = useAuthStore()

const currentYear = computed(() => new Date().getFullYear())

/** Navigation links (same sections as navbar) */
const navLinks = computed(() => getVisibleNavigationSections(
  portfolioStore.certifications,
  portfolioStore.experiences.length > 0
    || portfolioStore.experiencesLoading
    || Boolean(portfolioStore.experiencesError),
).map((section) => ({ label: section.label, href: `#${section.id}` })))

/**
 * Role line: extract the first part of the headline before "|"
 * Example: "Full-Stack Developer | Open Source Enthusiast" → "Full-Stack Developer"
 * Falls back to full headline if no "|" found.
 */
const roleText = computed(() => {
  const headline = portfolioStore.profile?.headline || ''
  const parts = headline.split('|').map(p => p.trim())
  return parts[0] || headline
})

/** Profile name from store */
const profileName = computed(() => portfolioStore.profile?.name || 'Portfolio')

/** Profile initials from store */
const profileInitials = computed(() => portfolioStore.profile?.initials || 'DEV')

/** Show admin lock icon: always visible if SHOW_ADMIN_LINK_TO_VISITORS is true, otherwise only when logged in */
const showAdminIcon = computed(() => SHOW_ADMIN_LINK_TO_VISITORS || authStore.isLoggedIn)

/** Loading state: true when profile data hasn't loaded yet */
const isLoading = computed(() => !portfolioStore.profile)

/**
 * Smoothly scroll to the very top of the page.
 * Uses instant scroll if user has prefers-reduced-motion enabled.
 */
function scrollToTop() {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  window.scrollTo({ top: 0, behavior: reducedMotion ? 'instant' : 'smooth' })
}
</script>

<template>
  <footer class="border-t border-line bg-surface transition-colors mt-auto">
    <AppContainer>
      <!-- Main footer content -->
      <div class="py-12">
        <!-- Desktop layout: 3 columns (brand | nav links | social icons) -->
        <!-- Medium: 2 rows (brand + icons on first row, nav links on second) -->
        <!-- Mobile: stacked -->
        <div class="footer-grid">
          <!-- Left: Brand (logo chip + name + role) -->
          <div class="footer-brand-col">
            <div class="flex items-center gap-2.5">
              <!-- Logo chip skeleton while loading -->
              <div
                v-if="isLoading"
                class="skeleton w-9 h-9 rounded-control"
                aria-hidden="true"
              />
              <span
                v-else
                class="flex items-center justify-center w-9 h-9 rounded-control bg-accent text-on-accent font-mono text-sm font-bold"
              >
                {{ profileInitials }}
              </span>

              <div class="flex flex-col">
                <!-- Name skeleton while loading -->
                <div
                  v-if="isLoading"
                  class="skeleton h-4 w-32 rounded"
                  aria-hidden="true"
                />
                <span
                  v-else
                  class="text-base font-semibold text-fg tracking-tight"
                >
                  {{ profileName }}
                </span>

                <!-- Role line (first part of headline before "|") -->
                <!-- Role skeleton while loading -->
                <div
                  v-if="isLoading"
                  class="skeleton h-3 w-40 rounded mt-1"
                  aria-hidden="true"
                />
                <span
                  v-else-if="roleText"
                  class="text-sm text-muted"
                >
                  {{ roleText }}
                </span>
              </div>
            </div>
          </div>

          <!-- Middle: Navigation links (horizontal row on desktop, wrapping on mobile) -->
          <nav
            class="footer-nav-col"
            aria-label="Footer"
          >
            <a
              v-for="link in navLinks"
              :key="link.href"
              :href="link.href"
              class="footer-nav-link"
            >
              {{ link.label }}
            </a>
          </nav>

          <!-- Right: Social icons (using shared SocialLinks component) -->
          <div class="footer-social-col">
            <!-- Social icons skeleton while loading -->
            <div
              v-if="isLoading"
              class="flex items-center gap-2"
              aria-hidden="true"
            >
              <div class="skeleton w-9 h-9 rounded-control" />
              <div class="skeleton w-9 h-9 rounded-control" />
              <div class="skeleton w-9 h-9 rounded-control" />
            </div>
            <SocialLinks v-else variant="icons" />
          </div>
        </div>
      </div>

      <!-- Bottom bar: copyright + admin lock icon + back to top -->
      <div class="footer-bottom">
        <div class="footer-bottom-left">
          <span class="text-sm text-muted">
            &copy; {{ currentYear }} {{ profileName }}. All rights reserved.
          </span>

          <!-- Admin lock icon (icon-only link to /admin) -->
          <RouterLink
            v-if="showAdminIcon"
            to="/admin"
            class="admin-lock-icon"
            aria-label="Admin login"
            title="Admin login"
          >
            <Icon icon="lucide:lock" width="16" height="16" aria-hidden="true" />
          </RouterLink>
        </div>

        <!-- Back to top button -->
        <button
          type="button"
          class="back-to-top-btn"
          @click="scrollToTop"
          aria-label="Back to top"
        >
          <span class="text-sm">Back to top</span>
          <Icon icon="lucide:arrow-up" width="16" height="16" aria-hidden="true" />
        </button>
      </div>
    </AppContainer>
  </footer>
</template>


<style scoped>
@reference "@/assets/main.css";

/* ── Footer grid layouts ─────────────────────────────────────── */

/* Desktop (lg+): 3 columns (brand | nav | social), all vertically centered */
.footer-grid {
  display: grid;
  gap: 2rem;
  align-items: center;
}

@media (min-width: 1024px) {
  .footer-grid {
    grid-template-columns: 1fr auto 1fr;
    gap: 3rem;
  }
}

/* Medium screens: brand left, social right on first row; nav on second row */
@media (min-width: 768px) and (max-width: 1023px) {
  .footer-grid {
    grid-template-columns: 1fr auto;
    grid-template-areas:
      "brand social"
      "nav nav";
  }

  .footer-brand-col {
    grid-area: brand;
  }

  .footer-nav-col {
    grid-area: nav;
  }

  .footer-social-col {
    grid-area: social;
    justify-self: end;
  }
}

/* Mobile: stacked, left-aligned */
@media (max-width: 767px) {
  .footer-grid {
    grid-template-columns: 1fr;
    align-items: flex-start;
  }
}

/* ── Navigation links ────────────────────────────────────────── */

.footer-nav-col {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem 1.25rem; /* gap-y-3 gap-x-5 */
  align-items: center;
}

@media (min-width: 1024px) {
  .footer-nav-col {
    justify-content: center;
    gap: 0 1.5rem; /* single horizontal row with equal spacing */
  }
}

.footer-nav-link {
  @apply text-sm text-fg hover-link focus-ring;
  /* 44px minimum touch height */
  min-height: 2.75rem;
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 0;
}

/* ── Social icons column ─────────────────────────────────────── */

.footer-social-col {
  display: flex;
  align-items: center;
}

@media (min-width: 1024px) {
  .footer-social-col {
    justify-content: flex-end;
  }
}

/* ── Bottom bar ──────────────────────────────────────────────── */

.footer-bottom {
  @apply py-6 border-t border-line;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

/* Mobile: stack copyright and back-to-top button */
@media (max-width: 639px) {
  .footer-bottom {
    flex-direction: column;
    align-items: flex-start;
  }
}

.footer-bottom-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

/* ── Admin lock icon ─────────────────────────────────────────── */

.admin-lock-icon {
  @apply text-muted focus-ring rounded-control;
  /* 44px minimum touch target using padding */
  padding: 0.875rem; /* (44px - 16px icon) / 2 = 14px = 0.875rem */
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: color var(--transition-hover),
              background-color var(--transition-hover);
}

@media (hover: hover) {
  .admin-lock-icon:hover,
  .admin-lock-icon:focus-visible {
    @apply text-accent bg-accent-soft;
  }
}

/* ── Back to top button ──────────────────────────────────────── */

.back-to-top-btn {
  @apply text-muted hover-link focus-ring rounded-control;
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  /* 44px minimum touch height */
  min-height: 2.75rem;
  padding: 0.5rem 0.75rem;
}
</style>
