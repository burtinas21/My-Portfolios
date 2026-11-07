/**
 * stores/portfolio.js — Portfolio Data Store
 * ───────────────────────────────────────────
 * The central store for public portfolio data, loaded in one request through
 * GET /site and refreshed after admin changes.
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { loadIcons } from '@iconify/vue'
import { getSite } from '@/api/site.js'
import { getProject } from '@/api/projects.js'
import { getSocialIcon } from '@/utils/socialIcons.js'

const UPDATE_CHANNEL = 'portfolio-updated'
const preloadedIcons = new Set()

function preloadPayloadIcons(payload) {
  const iconNames = [
    'lucide:arrow-up',
    'lucide:lock',
    'simple-icons:gmail',
    'lucide:globe',
    'lucide:award',
    ...(payload.socials || []).map(getSocialIcon),
    ...Object.values(payload.skills || {}).flatMap((category) =>
      category.map((skill) => skill.icon).filter(Boolean),
    ),
    ...(payload.certifications || []).map((certification) =>
      certification.issuer_icon || 'lucide:award',
    ),
  ]
  const newIcons = [...new Set(iconNames)].filter((icon) => !preloadedIcons.has(icon))

  if (!newIcons.length) return

  newIcons.forEach((icon) => preloadedIcons.add(icon))
  loadIcons(newIcons)
}

export const usePortfolioStore = defineStore('portfolio', () => {
  const profile = ref(null)
  const profileLoading = ref(false)
  const profileError = ref(null)

  const socials = ref([])
  const socialsLoading = ref(false)
  const socialsError = ref(null)

  const projects = ref([])
  const projectsLoading = ref(false)
  const projectsError = ref(null)

  const skills = ref({})
  const skillsLoading = ref(false)
  const skillsError = ref(null)

  const experiences = ref([])
  const experiencesLoading = ref(false)
  const experiencesError = ref(null)

  const certifications = ref([])
  const certificationsLoading = ref(true)
  const certificationsError = ref(null)

  const cv = ref({ available: false, size: null, updated_at: null })
  const assistant = ref({ enabled: false, greeting: '', suggested_questions: [] })
  const assistantError = ref(null)
  const initialLoading = ref(false)
  const loaded = ref(false)
  const lastFetchedAt = ref(null)
  let pendingRequest = null

  const projectTags = computed(() => {
    const tags = new Set(projects.value.map((project) => project.tag).filter(Boolean))
    return Array.from(tags).sort()
  })

  const skillCategories = computed(() => Object.keys(skills.value))

  function applyPayload(payload) {
    preloadPayloadIcons(payload)
    profile.value = payload.profile
    socials.value = payload.socials || []
    projects.value = payload.projects || []
    skills.value = payload.skills || {}
    experiences.value = payload.experiences || []
    certifications.value = payload.certifications || []
    cv.value = payload.cv || { available: false, size: null, updated_at: null }
    assistant.value = payload.assistant || { enabled: false, greeting: '', suggested_questions: [] }
    profileError.value = null
    socialsError.value = null
    projectsError.value = null
    skillsError.value = null
    experiencesError.value = null
    certificationsError.value = null
    assistantError.value = null
  }

  function setLoading(value) {
    profileLoading.value = value
    socialsLoading.value = value
    projectsLoading.value = value
    skillsLoading.value = value
    experiencesLoading.value = value
    certificationsLoading.value = value
  }

  function setErrors(message) {
    profileError.value = message
    socialsError.value = message
    projectsError.value = message
    skillsError.value = message
    experiencesError.value = message
    certificationsError.value = message
    assistantError.value = message
  }

  async function loadSite({ silent = false } = {}) {
    if (pendingRequest) return pendingRequest

    if (!silent) {
      initialLoading.value = true
      setLoading(true)
      setErrors(null)
    }

    pendingRequest = (async () => {
      try {
        const payload = await getSite()
        if (!payload) throw new Error('The site data response was empty.')
        applyPayload(payload)
        loaded.value = true
        lastFetchedAt.value = Date.now()
        return true
      } catch (error) {
        if (silent && loaded.value) {
          console.error('Unable to refresh portfolio data:', error)
        } else {
          setErrors(error.message || 'Failed to load portfolio data.')
        }
        return false
      } finally {
        if (!silent) {
          initialLoading.value = false
          setLoading(false)
        }
        pendingRequest = null
      }
    })()

    return pendingRequest
  }

  async function fetchAll() {
    if (loaded.value) return true
    return loadSite()
  }

  function announceUpdate() {
    if (typeof BroadcastChannel === 'undefined') return

    const channel = new BroadcastChannel(UPDATE_CHANNEL)
    channel.postMessage({ type: 'refresh' })
    channel.close()
  }

  async function refresh({ broadcast = true } = {}) {
    const succeeded = await loadSite({ silent: loaded.value })
    if (broadcast) announceUpdate()
    return succeeded
  }

  async function refreshIfStale(maxAgeMs = 5 * 60 * 1000) {
    if (lastFetchedAt.value && Date.now() - lastFetchedAt.value < maxAgeMs) return
    if (!loaded.value) return fetchAll()
    return loadSite({ silent: true })
  }

  async function fetchProject(id) {
    return getProject(id)
  }

  return {
    profile,
    profileLoading,
    profileError,
    socials,
    socialsLoading,
    socialsError,
    projects,
    projectsLoading,
    projectsError,
    skills,
    skillsLoading,
    skillsError,
    experiences,
    experiencesLoading,
    experiencesError,
    certifications,
    certificationsLoading,
    certificationsError,
    cv,
    assistant,
    assistantError,
    initialLoading,
    loaded,
    lastFetchedAt,
    projectTags,
    skillCategories,
    fetchAll,
    refresh,
    refreshIfStale,
    fetchProject,
  }
})
