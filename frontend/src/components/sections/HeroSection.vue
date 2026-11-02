<!--
  HeroSection.vue — Modern Developer Hero Section
  ────────────────────────────────────────────────
  The opening section of the portfolio with:
  - Ambient radial background glow
  - Live availability badge with pulsing indicator
  - Bold fluid typography (name, headline, bio)
  - Location & email quick details
  - Action buttons (View Projects, Contact Me)
  - Interactive social links bar with SVG icons
  - Avatar image or stylized geometric fallback avatar
  - Accessible skeleton placeholders during data fetch
-->

<script setup>
import { computed } from 'vue'
import { usePortfolioStore } from '@/stores/portfolio.js'
import { imageUrl } from '@/utils/url.js'
import AppContainer from '@/components/ui/AppContainer.vue'
import SkeletonBlock from '@/components/ui/SkeletonBlock.vue'
import ErrorState from '@/components/ui/ErrorState.vue'

const portfolioStore = usePortfolioStore()

const profile = computed(() => portfolioStore.profile)
const avatarUrl = computed(() => imageUrl(profile.value?.avatar))

function getSocialIcon(name) {
  const normalized = (name || '').toLowerCase()
  if (normalized.includes('git')) return 'github'
  if (normalized.includes('link')) return 'linkedin'
  if (normalized.includes('twit') || normalized.includes('x')) return 'twitter'
  if (normalized.includes('mail')) return 'mail'
  return 'globe'
}
</script>

<template>
  <section
    id="hero"
    aria-labelledby="hero-title"
    class="relative overflow-hidden pt-12 pb-20 md:pt-20 md:pb-28 border-b border-line/60"
  >
    <!-- Ambient background glow effect -->
    <div
      class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[550px] h-[350px] bg-accent/10 rounded-full blur-[120px] pointer-events-none -z-10"
      aria-hidden="true"
    />

    <AppContainer>
      <!-- Loading Skeleton State -->
      <div v-if="portfolioStore.profileLoading && !profile" class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
        <div class="lg:col-span-8 space-y-5">
          <SkeletonBlock height="2rem" width="12rem" class="rounded-full" />
          <SkeletonBlock height="4rem" width="85%" />
          <SkeletonBlock height="1.75rem" width="60%" />
          <SkeletonBlock height="5rem" width="100%" />
          <div class="flex gap-4 pt-2">
            <SkeletonBlock height="2.75rem" width="10rem" />
            <SkeletonBlock height="2.75rem" width="10rem" />
          </div>
        </div>
        <div class="lg:col-span-4 flex justify-center">
          <SkeletonBlock height="18rem" width="18rem" class="rounded-card" />
        </div>
      </div>

      <!-- Error State with Retry -->
      <div v-else-if="portfolioStore.profileError && !profile">
        <ErrorState
          :message="portfolioStore.profileError"
          @retry="portfolioStore.refresh()"
        />
      </div>

      <!-- Main Loaded Content -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
        <!-- Left: Text Information & Actions -->
        <div class="lg:col-span-7 xl:col-span-8 space-y-6">
          <!-- Availability Badge -->
          <div
            v-if="profile?.status"
            class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-surface border border-line shadow-card text-fg"
          >
            <span class="relative flex h-2.5 w-2.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75" />
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500" />
            </span>
            <span>{{ profile.status }}</span>
          </div>

          <!-- Main Heading -->
          <div class="space-y-3">
            <h1
              id="hero-title"
              class="text-display font-black tracking-tight text-fg leading-none"
            >
              Hi, I'm <span class="text-accent">{{ profile?.name || 'Developer' }}</span>.
            </h1>
            <p class="text-xl sm:text-2xl font-medium text-muted">
              {{ profile?.headline || 'Full-Stack Developer | Laravel & Vue.js' }}
            </p>
          </div>

          <!-- Bio Description -->
          <p class="text-base sm:text-lg text-muted/90 max-w-2xl leading-relaxed">
            {{ profile?.bio || 'Building modern, resilient web applications with a focus on seamless user experience, clean architecture, and reliable performance.' }}
          </p>

          <!-- Quick Metadata (Location + Direct Contact) -->
          <div class="flex flex-wrap items-center gap-y-2 gap-x-6 text-sm text-muted">
            <div v-if="profile?.location" class="flex items-center gap-1.5">
              <svg class="w-4 h-4 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                <circle cx="12" cy="10" r="3" />
              </svg>
              <span>{{ profile.location }}</span>
            </div>

            <div v-if="profile?.email" class="flex items-center gap-1.5">
              <svg class="w-4 h-4 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="20" height="16" x="2" y="4" rx="2" />
                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
              </svg>
              <a :href="`mailto:${profile.email}`" class="hover:text-accent transition-colors underline-offset-4 hover:underline">
                {{ profile.email }}
              </a>
            </div>
          </div>

          <!-- Action Buttons & Social Links -->
          <div class="pt-2 flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex items-center gap-3">
              <a
                href="#work"
                class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-control text-sm font-semibold bg-accent text-on-accent hover:bg-accent-hover shadow-card transition-all duration-150 active:scale-95"
              >
                <span>Explore Projects</span>
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="m6 9 6 6 6-6" />
                </svg>
              </a>

              <a
                href="#contact"
                class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-control text-sm font-semibold border border-line bg-surface text-fg hover:bg-surface-2 transition-all duration-150 active:scale-95"
              >
                <span>Get In Touch</span>
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M5 12h14m-7-7 7 7-7 7" />
                </svg>
              </a>
            </div>

            <!-- Social Links Strip -->
            <div v-if="portfolioStore.socials.length" class="flex items-center gap-2 pt-2 sm:pt-0 sm:pl-4 sm:border-l border-line">
              <a
                v-for="social in portfolioStore.socials"
                :key="social.id"
                :href="social.url"
                target="_blank"
                rel="noopener noreferrer"
                :title="social.name"
                class="w-9 h-9 rounded-control flex items-center justify-center text-muted hover:text-accent hover:bg-surface-2 transition-colors border border-transparent hover:border-line"
              >
                <svg v-if="getSocialIcon(social.name) === 'github'" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                  <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" />
                </svg>
                <svg v-else-if="getSocialIcon(social.name) === 'linkedin'" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.46 8.76a1.64 1.64 0 1 0 0-3.28 1.64 1.64 0 0 0 0 3.28m1.4 9.74v-8.37H5.06v8.37z" />
                </svg>
                <svg v-else-if="getSocialIcon(social.name) === 'twitter'" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                </svg>
                <svg v-else class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10" />
                  <line x1="2" y1="12" x2="22" y2="12" />
                  <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                </svg>
              </a>
            </div>
          </div>
        </div>

        <!-- Right: Modern Avatar Card -->
        <div class="lg:col-span-5 xl:col-span-4 flex justify-center">
          <div class="relative group">
            <!-- Glow background -->
            <div
              class="absolute -inset-1.5 bg-gradient-to-tr from-accent to-violet-500 rounded-card opacity-30 group-hover:opacity-50 blur-lg transition duration-500"
              aria-hidden="true"
            />

            <div
              class="relative w-64 h-64 sm:w-72 sm:h-72 rounded-card bg-surface border border-line p-3 shadow-overlay flex flex-col items-center justify-center overflow-hidden"
            >
              <!-- Real Avatar Image if available -->
              <img
                v-if="avatarUrl"
                :src="avatarUrl"
                :alt="profile?.name || 'Developer Avatar'"
                class="w-full h-full object-cover rounded-control"
              />

              <!-- Elegant Stylized Initial/Code fallback -->
              <div
                v-else
                class="w-full h-full rounded-control bg-gradient-to-br from-surface-2 via-surface to-accent/10 flex flex-col items-center justify-center p-6 text-center select-none"
              >
                <div class="w-20 h-20 rounded-full bg-accent text-on-accent flex items-center justify-center font-mono font-black text-2xl shadow-card mb-4">
                  {{ profile?.initials || 'DEV' }}
                </div>
                <div class="font-mono text-xs font-semibold text-accent uppercase tracking-wider mb-1">
                  Full-Stack Architecture
                </div>
                <div class="text-xs text-muted">
                  Laravel &bull; Vue &bull; Clean Code
                </div>
              </div>


            </div>
          </div>
        </div>
      </div>
    </AppContainer>
  </section>
</template>
