<!--
  TheFooter.vue — Public Site Footer
  ───────────────────────────────────
  Clean, modern footer featuring:
  - Personal branding & availability status
  - Dynamic social links from the store
  - Quick section links
  - "Back to top" smooth scrolling button
  - Copyright and subtle admin portal link
-->

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { usePortfolioStore } from '@/stores/portfolio.js'

const portfolioStore = usePortfolioStore()
const currentYear = computed(() => new Date().getFullYear())

const navLinks = [
  { label: 'About', href: '#hero' },
  { label: 'Work', href: '#work' },
  { label: 'Skills', href: '#skills' },
  { label: 'Experience', href: '#experience' },
  { label: 'Contact', href: '#contact' },
]

/**
 * Return an SVG path or icon identifier based on social link name
 */
function getSocialIcon(name) {
  const normalized = (name || '').toLowerCase()
  if (normalized.includes('git')) return 'github'
  if (normalized.includes('link')) return 'linkedin'
  if (normalized.includes('twit') || normalized.includes('x')) return 'twitter'
  if (normalized.includes('mail')) return 'mail'
  return 'globe'
}

/**
 * Smoothly scroll to the very top of the page
 */
function scrollToTop() {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
</script>

<template>
  <footer class="border-t border-line bg-surface transition-colors mt-auto">
    <div class="container-main py-12 md:py-16">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-start">
        <!-- Col 1: Brand & Bio -->
        <div class="space-y-3">
          <div class="flex items-center gap-2">
            <span
              class="flex items-center justify-center w-8 h-8 rounded-control bg-accent text-on-accent font-mono text-xs font-bold"
            >
              {{ portfolioStore.profile?.initials || 'DEV' }}
            </span>
            <span class="font-bold text-lg text-fg tracking-tight">
              {{ portfolioStore.profile?.name || 'Developer Portfolio' }}
            </span>
          </div>

          <p class="text-sm text-muted max-w-sm">
            {{ portfolioStore.profile?.headline || 'Full-Stack Developer crafting high-performance, elegant web applications.' }}
          </p>

          <!-- Availability Status Indicator -->
          <div
            v-if="portfolioStore.profile?.status"
            class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
          >
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
            <span>{{ portfolioStore.profile.status }}</span>
          </div>
        </div>

        <!-- Col 2: Navigation Links -->
        <div class="space-y-2">
          <h3 class="text-xs font-semibold uppercase tracking-wider text-muted">
            Navigation
          </h3>
          <ul class="flex flex-wrap md:flex-col gap-x-4 gap-y-2 text-sm">
            <li v-for="link in navLinks" :key="link.href">
              <a
                :href="link.href"
                class="hover-link"
              >
                {{ link.label }}
              </a>
            </li>
          </ul>
        </div>

        <!-- Col 3: Connect & Socials -->
        <div class="space-y-3">
          <h3 class="text-xs font-semibold uppercase tracking-wider text-muted">
            Connect
          </h3>
          <p class="text-xs text-muted">
            Feel free to connect.
          </p>

          <!-- Social link icons -->
          <div class="flex flex-wrap items-center gap-2">
            <a
              v-for="social in portfolioStore.socials"
              :key="social.id"
              :href="social.url"
              target="_blank"
              rel="noopener noreferrer"
              :title="social.name"
              class="w-9 h-9 rounded-control flex items-center justify-center border border-line bg-surface-2 text-muted hover-icon focus-ring"
            >
              <!-- GitHub Icon -->
              <svg v-if="getSocialIcon(social.name) === 'github'" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" />
              </svg>
              <!-- LinkedIn Icon -->
              <svg v-else-if="getSocialIcon(social.name) === 'linkedin'" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.46 8.76a1.64 1.64 0 1 0 0-3.28 1.64 1.64 0 0 0 0 3.28m1.4 9.74v-8.37H5.06v8.37z" />
              </svg>
              <!-- Twitter/X Icon -->
              <svg v-else-if="getSocialIcon(social.name) === 'twitter'" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
              </svg>
              <!-- Generic Globe/Link Icon -->
              <svg v-else class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10" />
                <line x1="2" y1="12" x2="22" y2="12" />
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
              </svg>
            </a>
          </div>
        </div>
      </div>

      <!-- Bottom Bar: Copyright & Back To Top -->
      <div class="mt-12 pt-6 border-t border-line flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-muted">
        <div class="flex items-center gap-3">
          <span>&copy; {{ currentYear }} {{ portfolioStore.profile?.name || 'Portfolio' }}. All rights reserved.</span>
          <span class="text-line">&bull;</span>
          <RouterLink to="/admin" class="hover-link focus-ring">
            Admin Area
          </RouterLink>
        </div>

        <button
          type="button"
          class="inline-flex items-center gap-1.5 hover-link focus-ring p-1"
          @click="scrollToTop"
        >
          <span>Back to top</span>
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m18 15-6-6-6 6" />
          </svg>
        </button>
      </div>
    </div>
  </footer>
</template>
