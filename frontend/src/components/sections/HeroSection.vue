<!--
  HeroSection.vue — Modern Developer Hero Section
  ────────────────────────────────────────────────
  The opening section of the portfolio with:
  - Ambient radial background glow
  - Live availability badge with pulsing indicator
  - Bold fluid typography (name, headline, bio)
  - Location quick detail
  - Action buttons (View Projects, Contact Me)
  - Interactive social links row
  - Avatar image or stylized geometric fallback avatar
  - Accessible skeleton placeholders during data fetch
-->

<script setup>
import { computed } from 'vue'
import { usePortfolioStore } from '@/stores/portfolio.js'
import { assetUrl } from '@/utils/url.js'
import AppContainer from '@/components/ui/AppContainer.vue'
import CvDownloadButton from '@/components/ui/CvDownloadButton.vue'
import SocialLinks from '@/components/ui/SocialLinks.vue'
import SkeletonBlock from '@/components/ui/SkeletonBlock.vue'
import ErrorState from '@/components/ui/ErrorState.vue'

const portfolioStore = usePortfolioStore()

const profile = computed(() => portfolioStore.profile)
const avatarUrl = computed(() => assetUrl(profile.value?.avatar_optimized || profile.value?.avatar))
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

            <CvDownloadButton
              v-if="portfolioStore.cv.available"
              variant="inline"
              :available="portfolioStore.cv.available"
            />
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

            <SocialLinks
              variant="icons"
              class="pt-2 sm:pt-0 sm:pl-4 sm:border-l border-line"
            />
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
                :srcset="profile?.avatar_optimized_srcset || undefined"
                :alt="profile?.name || 'Developer Avatar'"
                width="400"
                height="400"
                sizes="(min-width: 640px) 264px, 232px"
                fetchpriority="high"
                decoding="async"
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
