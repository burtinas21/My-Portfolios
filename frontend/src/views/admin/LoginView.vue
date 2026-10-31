<!--
  LoginView.vue — Admin Login Page
  ─────────────────────────────────
  A clean, centered login form for the admin panel.
  - Calls POST /login via the auth store
  - Redirects to /admin (or ?redirect query) on success
  - Shows validation errors inline
  - Redirects to /admin if already logged in
-->

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import BaseButton from '@/components/ui/BaseButton.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

// If already logged in, skip straight to admin
onMounted(() => {
  if (authStore.isLoggedIn) {
    router.replace({ name: 'admin-dashboard' })
  }
})

const form = reactive({ email: '', password: '' })
const errors = reactive({ email: '', password: '' })
const serverError = ref('')
const isSubmitting = ref(false)

function validate() {
  errors.email = ''
  errors.password = ''
  serverError.value = ''
  let valid = true

  if (!form.email.trim()) {
    errors.email = 'Email is required.'
    valid = false
  }
  if (!form.password) {
    errors.password = 'Password is required.'
    valid = false
  }
  return valid
}

async function handleSubmit() {
  if (!validate()) return
  isSubmitting.value = true

  try {
    await authStore.login({ email: form.email, password: form.password })
    const redirect = route.query.redirect
    router.replace(typeof redirect === 'string' ? redirect : { name: 'admin-dashboard' })
  } catch (err) {
    if (err.fieldErrors?.email) {
      errors.email = err.fieldErrors.email[0]
    } else {
      serverError.value = err.message || 'Login failed. Please check your credentials.'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-bg flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
      <!-- Brand mark -->
      <div class="text-center mb-8 space-y-2">
        <div class="w-12 h-12 rounded-card bg-accent flex items-center justify-center text-on-accent font-bold text-lg mx-auto">
          A
        </div>
        <h1 class="text-2xl font-bold text-fg">Admin Login</h1>
        <p class="text-sm text-muted">Sign in to manage your portfolio</p>
      </div>

      <!-- Form card -->
      <div class="bg-surface border border-line rounded-card shadow-card p-6 sm:p-8 space-y-5">
        <!-- Server error banner -->
        <div
          v-if="serverError"
          class="p-3.5 rounded-control bg-danger/10 border border-danger/30 text-danger text-sm"
          role="alert"
        >
          {{ serverError }}
        </div>

        <form class="space-y-4" novalidate @submit.prevent="handleSubmit">
          <!-- Email -->
          <div class="space-y-1.5">
            <label for="login-email" class="block text-sm font-medium text-fg">Email</label>
            <input
              id="login-email"
              v-model="form.email"
              type="email"
              autocomplete="email"
              placeholder="admin@example.com"
              class="w-full px-3.5 py-2.5 rounded-control border bg-surface text-sm text-fg
                     placeholder:text-muted transition-colors outline-none
                     focus:ring-2 focus:ring-accent focus:border-accent"
              :class="errors.email ? 'border-danger' : 'border-line'"
            />
            <p v-if="errors.email" class="text-xs text-danger">{{ errors.email }}</p>
          </div>

          <!-- Password -->
          <div class="space-y-1.5">
            <label for="login-password" class="block text-sm font-medium text-fg">Password</label>
            <input
              id="login-password"
              v-model="form.password"
              type="password"
              autocomplete="current-password"
              placeholder="••••••••"
              class="w-full px-3.5 py-2.5 rounded-control border bg-surface text-sm text-fg
                     placeholder:text-muted transition-colors outline-none
                     focus:ring-2 focus:ring-accent focus:border-accent"
              :class="errors.password ? 'border-danger' : 'border-line'"
            />
            <p v-if="errors.password" class="text-xs text-danger">{{ errors.password }}</p>
          </div>

          <BaseButton
            type="submit"
            variant="primary"
            size="md"
            class="w-full"
            :loading="isSubmitting"
          >
            Sign In
          </BaseButton>
        </form>
      </div>

      <!-- Back to site link -->
      <p class="text-center mt-5 text-sm text-muted">
        <a href="/" class="text-accent hover:underline">← Back to portfolio</a>
      </p>
    </div>
  </div>
</template>
