/**
 * router/index.js — Vue Router Configuration
 * ────────────────────────────────────────────
 * Defines all routes for the public site and admin panel.
 *
 * Architecture:
 * - Public routes use PublicLayout (navbar + footer)
 * - Admin routes use AdminLayout (sidebar + top bar)
 * - Admin views are LAZY-LOADED (code-split) to keep the public bundle small
 * - A global navigation guard protects /admin/* routes
 * - Scroll behavior restores position on back/forward navigation
 * - Route meta.title sets the document title
 *
 * Used by: main.js (app.use(router))
 */

import { createRouter, createWebHistory } from 'vue-router'
import PublicLayout from '@/layouts/PublicLayout.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),

  /**
   * Scroll behavior:
   * - If the user navigates back/forward, restore their scroll position
   * - If navigating to a hash (#work, #skills), smooth-scroll to that section
   * - Otherwise, scroll to the top of the page
   */
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition
    if (to.hash) return { el: to.hash, behavior: 'smooth' }
    return { top: 0, behavior: 'smooth' }
  },

  routes: [
    /* ── Public routes (wrapped in PublicLayout) ─────────────── */
    {
      path: '/',
      component: PublicLayout,
      children: [
        {
          path: '',
          name: 'home',
          component: () => import('@/views/public/HomeView.vue'),
          meta: { title: 'Home' },
        },
      ],
    },

    /* ── Admin routes (lazy-loaded, wrapped in AdminLayout) ──── */
    {
      path: '/admin/login',
      name: 'admin-login',
      component: () => import('@/views/admin/LoginView.vue'),
      meta: { title: 'Login', isPublic: true },
    },
    {
      path: '/admin',
      component: () => import('@/layouts/AdminLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'admin-dashboard',
          component: () => import('@/views/admin/DashboardView.vue'),
          meta: { title: 'Dashboard' },
        },
        {
          path: 'profile',
          name: 'admin-profile',
          component: () => import('@/views/admin/ProfileView.vue'),
          meta: { title: 'Profile' },
        },
        {
          path: 'projects',
          name: 'admin-projects',
          component: () => import('@/views/admin/ProjectsView.vue'),
          meta: { title: 'Projects' },
        },
        {
          path: 'skills',
          name: 'admin-skills',
          component: () => import('@/views/admin/SkillsView.vue'),
          meta: { title: 'Skills' },
        },
        {
          path: 'experience',
          name: 'admin-experience',
          component: () => import('@/views/admin/ExperienceView.vue'),
          meta: { title: 'Experience' },
        },
        {
          path: 'socials',
          name: 'admin-socials',
          component: () => import('@/views/admin/SocialsView.vue'),
          meta: { title: 'Socials' },
        },
        {
          path: 'messages',
          name: 'admin-messages',
          component: () => import('@/views/admin/MessagesView.vue'),
          meta: { title: 'Messages' },
        },
      ],
    },

    /* ── 404 catch-all ────────────────────────────────────────── */
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/public/NotFoundView.vue'),
      meta: { title: 'Page Not Found' },
    },
  ],
})

/* ── Global navigation guard ───────────────────────────────────── */
/*
 * This runs BEFORE every route change. It does two things:
 *
 * 1. AUTH CHECK: If the target route (or any parent) has
 *    meta.requiresAuth and the user isn't logged in, redirect
 *    to the login page with ?redirect=... so we can come back after login.
 *
 * 2. TITLE: Set the document title from route.meta.title.
 */
router.beforeEach((to) => {
  // Check if the route requires authentication
  const requiresAuth = to.matched.some((record) => record.meta.requiresAuth)

  if (requiresAuth) {
    // Check for a token in localStorage (fast, synchronous check)
    const token = localStorage.getItem('auth_token')

    if (!token) {
      // Not logged in → redirect to login with the original destination
      return {
        name: 'admin-login',
        query: { redirect: to.fullPath },
      }
    }
  }

  // Set the document title
  const appTitle = import.meta.env.VITE_APP_TITLE || 'Portfolio'
  const pageTitle = to.meta.title
  document.title = pageTitle ? `${pageTitle} | ${appTitle}` : appTitle
})

export default router