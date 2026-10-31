<!--
  DashboardView.vue — Admin Dashboard Overview
  ─────────────────────────────────────────────
  Shows a quick-stats summary and quick-action links to all admin sections.
  Data is fetched from the API on mount.
-->

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { getAdminProjects } from '@/api/admin/projects.js'
import { getAdminSkills } from '@/api/admin/skills.js'
import { getAdminExperiences } from '@/api/admin/experiences.js'
import { getAdminMessages } from '@/api/admin/messages.js'
import { getAdminSocials } from '@/api/admin/socials.js'

const stats = ref([
  { label: 'Projects', value: '—', to: { name: 'admin-projects' }, icon: `<rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>`, color: 'text-violet-500 bg-violet-500/10' },
  { label: 'Skills', value: '—', to: { name: 'admin-skills' }, icon: `<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>`, color: 'text-blue-500 bg-blue-500/10' },
  { label: 'Experiences', value: '—', to: { name: 'admin-experience' }, icon: `<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/>`, color: 'text-emerald-500 bg-emerald-500/10' },
  { label: 'Unread Messages', value: '—', to: { name: 'admin-messages' }, icon: `<path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>`, color: 'text-amber-500 bg-amber-500/10' },
  { label: 'Social Links', value: '—', to: { name: 'admin-socials' }, icon: `<path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>`, color: 'text-pink-500 bg-pink-500/10' },
])

const quickLinks = [
  { label: 'Edit Profile', description: 'Update your bio, headline and avatar', to: { name: 'admin-profile' }, icon: `<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>` },
  { label: 'New Project', description: 'Add a new portfolio project', to: { name: 'admin-projects' }, icon: `<rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>` },
  { label: 'Add Skill', description: 'Update your technical skill set', to: { name: 'admin-skills' }, icon: `<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>` },
  { label: 'Messages', description: 'Read incoming contact messages', to: { name: 'admin-messages' }, icon: `<path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>` },
]

onMounted(async () => {
  const [projects, skills, experiences, messages, socials] = await Promise.allSettled([
    getAdminProjects(),
    getAdminSkills(),
    getAdminExperiences(),
    getAdminMessages(),
    getAdminSocials(),
  ])

  if (projects.status === 'fulfilled') stats.value[0].value = projects.value.length
  if (skills.status === 'fulfilled') stats.value[1].value = skills.value.length
  if (experiences.status === 'fulfilled') stats.value[2].value = experiences.value.length
  if (messages.status === 'fulfilled') {
    const unread = messages.value.filter((m) => !m.read_at).length
    stats.value[3].value = unread
    stats.value[3].label = unread === 1 ? 'Unread Message' : 'Unread Messages'
  }
  if (socials.status === 'fulfilled') stats.value[4].value = socials.value.length
})
</script>

<template>
  <div class="space-y-8 max-w-5xl">
    <!-- Header -->
    <div>
      <h2 class="text-2xl font-bold text-fg">Dashboard</h2>
      <p class="text-sm text-muted mt-1">Overview of your portfolio content.</p>
    </div>

    <!-- Stats grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
      <RouterLink
        v-for="stat in stats"
        :key="stat.label"
        :to="stat.to"
        class="flex flex-col gap-3 p-5 rounded-card border border-line bg-surface shadow-card hover:border-accent/50 transition-colors"
      >
        <div :class="['w-10 h-10 rounded-control flex items-center justify-center shrink-0', stat.color]">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" v-html="stat.icon" />
        </div>
        <div>
          <p class="text-2xl font-bold text-fg leading-none">{{ stat.value }}</p>
          <p class="text-xs text-muted mt-1">{{ stat.label }}</p>
        </div>
      </RouterLink>
    </div>

    <!-- Quick actions -->
    <div>
      <h3 class="text-sm font-semibold text-muted uppercase tracking-wider mb-4">Quick Actions</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <RouterLink
          v-for="link in quickLinks"
          :key="link.label"
          :to="link.to"
          class="flex items-center gap-4 p-4 rounded-card border border-line bg-surface shadow-card hover:border-accent/50 hover:bg-surface-2 transition-colors"
        >
          <div class="w-10 h-10 rounded-control bg-accent-soft text-accent flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" v-html="link.icon" />
          </div>
          <div>
            <p class="text-sm font-semibold text-fg">{{ link.label }}</p>
            <p class="text-xs text-muted">{{ link.description }}</p>
          </div>
          <svg class="w-4 h-4 text-muted ml-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 18l6-6-6-6"/></svg>
        </RouterLink>
      </div>
    </div>
  </div>
</template>
