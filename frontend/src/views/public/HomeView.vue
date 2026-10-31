<!--
  HomeView.vue — Public Single-Page Experience
  ─────────────────────────────────────────────
  The main landing page for the portfolio.
  Orchestrates the public narrative sections:
  1. HeroSection — Introduction, bio, availability & avatar
  2. WorkSection — Filterable project showcase & detail modal
  3. SkillsSection — Grouped technical skill proficiency bars
  4. ExperienceSection — Career history & work milestones
  5. ContactSection — Interactive message form & contact details

  Also configures reactive SEO meta tags based on backend profile data.
-->

<script setup>
import { computed, watchEffect } from 'vue'
import { usePortfolioStore } from '@/stores/portfolio.js'
import { useSeo } from '@/composables/useSeo.js'
import { useReveal } from '@/composables/useReveal.js'
import HeroSection from '@/components/sections/HeroSection.vue'
import WorkSection from '@/components/sections/WorkSection.vue'
import SkillsSection from '@/components/sections/SkillsSection.vue'
import ExperienceSection from '@/components/sections/ExperienceSection.vue'
import ContactSection from '@/components/sections/ContactSection.vue'

const portfolioStore = usePortfolioStore()

// Setup scroll-reveal animation observer
const { revealRef } = useReveal()

// Reactive SEO values derived from the store profile
const pageTitle = computed(() => {
  if (portfolioStore.profile?.name) {
    return `${portfolioStore.profile.name} — ${portfolioStore.profile.headline || 'Full-Stack Developer'}`
  }
  return 'Full-Stack Developer Portfolio'
})

const pageDescription = computed(() => {
  return portfolioStore.profile?.bio || 'Explore modern web projects, technical expertise, and engineering experience.'
})

const { apply: applySeo } = useSeo({
  title: pageTitle,
  description: pageDescription,
})

// Re-apply SEO tags once backend profile data loads
watchEffect(() => {
  if (portfolioStore.profile) {
    applySeo()
  }
})
</script>

<template>
  <div ref="revealRef" class="w-full">
    <!-- Hero Section -->
    <HeroSection />

    <!-- Featured Projects Section -->
    <WorkSection />

    <!-- Technical Skills Section -->
    <SkillsSection />

    <!-- Career Milestones Timeline -->
    <ExperienceSection />

    <!-- Interactive Contact Form -->
    <ContactSection />
  </div>
</template>