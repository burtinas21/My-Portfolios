/**
 * stores/portfolio.js — Portfolio Data Store
 * ───────────────────────────────────────────
 * The central store for ALL public portfolio data: profile, projects,
 * skills, experiences, and social links.
 *
 * Design decisions:
 * 1. LOAD ONCE: fetchAll() loads everything in parallel, then caches it.
 *    It won't fetch again if data is already loaded (use refresh() to force).
 * 2. NO DUPLICATE FETCHES: A loading flag prevents two components from
 *    triggering the same fetch at the same time.
 * 3. SEPARATION: each resource has its own loading/error state so
 *    one failing API call doesn't break the whole page.
 * 4. REFRESH: after admin changes, call refresh() to re-fetch everything
 *    so the public site shows the latest data.
 *
 * Data flow: Component → this store → src/api/*.js → http.js → Laravel
 *
 * Used by: all public page sections (Hero, Work, Skills, Experience, Contact)
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { getProfile } from '@/api/profile.js'
import { getSocials } from '@/api/socials.js'
import { getProjects, getProject } from '@/api/projects.js'
import { getSkills } from '@/api/skills.js'
import { getExperiences } from '@/api/experiences.js'

export const usePortfolioStore = defineStore('portfolio', () => {
  /* ── State for each resource ───────────────────────────────── */

  /* Profile */
  const profile = ref(null)
  const profileLoading = ref(false)
  const profileError = ref(null)

  /* Social links */
  const socials = ref([])
  const socialsLoading = ref(false)
  const socialsError = ref(null)

  /* Projects (published only for public site) */
  const projects = ref([])
  const projectsLoading = ref(false)
  const projectsError = ref(null)

  /* Skills (grouped object: { "Frontend": [...], "Backend": [...] }) */
  const skills = ref({})
  const skillsLoading = ref(false)
  const skillsError = ref(null)

  /* Experiences */
  const experiences = ref([])
  const experiencesLoading = ref(false)
  const experiencesError = ref(null)

  /* Master loading flag: true while the initial fetchAll is running */
  const initialLoading = ref(false)
  /* Tracks whether fetchAll has completed at least once */
  const loaded = ref(false)

  /* Timestamp (ms) of the last successful data fetch — used by refreshIfStale() */
  const lastFetchedAt = ref(null)

  /*
   * Private flag (not reactive — no component needs to read it):
   * prevents overlapping silent refreshes from piling up.
   */
  let _isRefreshing = false

  /* ── Computed values ───────────────────────────────────────── */

  /**
   * Unique project tags (e.g. ["Web App", "Mobile", "CLI"]).
   * Used by WorkSection to build filter chips.
   */
  const projectTags = computed(() => {
    const tags = new Set(projects.value.map((p) => p.tag).filter(Boolean))
    return Array.from(tags).sort()
  })

  /**
   * Category names from the skills object (e.g. ["Backend", "Frontend"]).
   */
  const skillCategories = computed(() => Object.keys(skills.value))

  /* ── Actions ───────────────────────────────────────────────── */

  /**
   * Load ALL portfolio data in parallel.
   * Skips if data is already loaded (call refresh() to force).
   * Prevents duplicate concurrent fetches.
   */
  async function fetchAll() {
    // Don't fetch if already loaded or currently loading
    if (loaded.value || initialLoading.value) return

    initialLoading.value = true

    // Fetch all resources in parallel using Promise.allSettled
    // (allSettled never rejects — each promise resolves with its status)
    await Promise.allSettled([
      fetchProfile(),
      fetchSocials(),
      fetchProjects(),
      fetchSkills(),
      fetchExperiences(),
    ])

    loaded.value = true
    lastFetchedAt.value = Date.now()
    initialLoading.value = false
  }

  /**
   * Force re-fetch everything (e.g. after an admin update).
   */
  async function refresh() {
    loaded.value = false
    await fetchAll()
  }

  /**
   * Silent, conditional refresh — called by the useAutoRefresh composable
   * (and can be called from anywhere that needs a non-disruptive update).
   *
   * Only re-fetches if the data is older than maxAgeMs (default 5 min)
   * AND no other silent refresh is already in progress.
   *
   * Key behaviour:
   * - Does NOT reset loading/error flags → no skeletons, no flicker.
   * - Keeps showing the current data until the new response arrives.
   * - If any individual request fails, the old data for that resource
   *   is kept and NO error is surfaced to the user.
   *
   * @param {number} maxAgeMs — maximum acceptable data age in milliseconds.
   */
  async function refreshIfStale(maxAgeMs = 5 * 60 * 1000) {
    // Guard: skip if a silent refresh is already running
    if (_isRefreshing) return

    // Guard: skip if data was fetched recently enough
    if (lastFetchedAt.value && (Date.now() - lastFetchedAt.value) < maxAgeMs) return

    // Guard: skip if the initial load hasn't finished yet (fetchAll is in progress)
    if (!loaded.value) return

    _isRefreshing = true

    try {
      // Fetch every resource in parallel without touching any loading/error refs
      const [profileRes, socialsRes, projectsRes, skillsRes, experiencesRes] =
        await Promise.allSettled([
          getProfile(),
          getSocials(),
          getProjects(),
          getSkills(),
          getExperiences(),
        ])

      // Only swap in data for requests that succeeded —
      // failed ones silently keep the previous value.
      if (profileRes.status === 'fulfilled')     profile.value     = profileRes.value
      if (socialsRes.status === 'fulfilled')     socials.value     = socialsRes.value
      if (projectsRes.status === 'fulfilled')    projects.value    = projectsRes.value
      if (skillsRes.status === 'fulfilled')      skills.value      = skillsRes.value
      if (experiencesRes.status === 'fulfilled') experiences.value = experiencesRes.value

      lastFetchedAt.value = Date.now()
    } finally {
      _isRefreshing = false
    }
  }

  /* ── Individual fetch helpers ──────────────────────────────── */
  /* Each one sets its own loading/error state independently */

  async function fetchProfile() {
    profileLoading.value = true
    profileError.value = null
    try {
      profile.value = await getProfile()
    } catch (err) {
      profileError.value = err.message || 'Failed to load profile'
    } finally {
      profileLoading.value = false
    }
  }

  async function fetchSocials() {
    socialsLoading.value = true
    socialsError.value = null
    try {
      socials.value = await getSocials()
    } catch (err) {
      socialsError.value = err.message || 'Failed to load social links'
    } finally {
      socialsLoading.value = false
    }
  }

  async function fetchProjects() {
    projectsLoading.value = true
    projectsError.value = null
    try {
      projects.value = await getProjects()
    } catch (err) {
      projectsError.value = err.message || 'Failed to load projects'
    } finally {
      projectsLoading.value = false
    }
  }

  async function fetchSkills() {
    skillsLoading.value = true
    skillsError.value = null
    try {
      skills.value = await getSkills()
    } catch (err) {
      skillsError.value = err.message || 'Failed to load skills'
    } finally {
      skillsLoading.value = false
    }
  }

  async function fetchExperiences() {
    experiencesLoading.value = true
    experiencesError.value = null
    try {
      experiences.value = await getExperiences()
    } catch (err) {
      experiencesError.value = err.message || 'Failed to load experiences'
    } finally {
      experiencesLoading.value = false
    }
  }

  /**
   * Fetch a single project by ID (for the detail view/modal).
   * @param {number|string} id
   * @returns {Promise<object>}
   */
  async function fetchProject(id) {
    return await getProject(id)
  }

  return {
    /* State */
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
    initialLoading,
    loaded,
    lastFetchedAt,

    /* Computed */
    projectTags,
    skillCategories,

    /* Actions */
    fetchAll,
    refresh,
    refreshIfStale,
    fetchProject,
  }
})
