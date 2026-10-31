/**
 * main.js — Application Entry Point
 * ──────────────────────────────────
 * This is the first JavaScript file that runs.
 * It creates the Vue app, registers plugins (Pinia, Router),
 * imports global styles, and mounts the app to the DOM.
 *
 * It also initializes the theme (so there's no flash of wrong colors)
 * and attempts to restore the admin session if a token exists.
 */

/* Global styles (design tokens, Tailwind, animations) */
import '@/assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from '@/App.vue'
import router from '@/router/index.js'

/* Initialize the theme immediately (before mount) so the user
   doesn't see a flash of the wrong theme */
import { useTheme } from '@/composables/useTheme.js'

const app = createApp(App)

/* Pinia must be installed BEFORE any store is used */
app.use(createPinia())
app.use(router)

/* Restore the admin session in the background.
   We don't await this because we don't want to block the initial render —
   the public site doesn't need auth. The admin router guard already
   checks for a token synchronously, and the auth store's restoreSession
   will verify it asynchronously. */
import { useAuthStore } from '@/stores/auth.js'
const authStore = useAuthStore()
authStore.restoreSession()

app.mount('#app')