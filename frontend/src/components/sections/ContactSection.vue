<!--
  ContactSection.vue — Interactive Contact Form & Details
  ────────────────────────────────────────────────────────
  Enables visitors to send messages directly to the portfolio owner:
  - Form fields: Name, Email, Message body
  - Client-side real-time validation (required, email format, lengths)
  - Submission via POST /api/messages with loading spinner
  - Inline success feedback & toast notifications
  - Rate limiting (429) & backend validation (422) handling
  - Direct contact information side card (Email, Location, Socials)
-->

<script setup>
import { ref, reactive } from 'vue'
import { usePortfolioStore } from '@/stores/portfolio.js'
import { sendMessage } from '@/api/messages.js'
import { useToast } from '@/composables/useToast.js'
import AppContainer from '@/components/ui/AppContainer.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import SocialLinks from '@/components/ui/SocialLinks.vue'

const portfolioStore = usePortfolioStore()
const toast = useToast()

// Form reactive model
const form = reactive({
  name: '',
  email: '',
  body: '',
})

// Validation errors for each field
const errors = reactive({
  name: '',
  email: '',
  body: '',
})

const isSubmitting = ref(false)
const isSubmitted = ref(false)
const serverError = ref('')

/**
 * Validate form fields before submitting
 * @returns {boolean} true if valid
 */
function validate() {
  let isValid = true
  errors.name = ''
  errors.email = ''
  errors.body = ''
  serverError.value = ''

  if (!form.name.trim()) {
    errors.name = 'Please enter your name.'
    isValid = false
  } else if (form.name.length > 100) {
    errors.name = 'Name must not exceed 100 characters.'
    isValid = false
  }

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!form.email.trim()) {
    errors.email = 'Please enter your email address.'
    isValid = false
  } else if (!emailPattern.test(form.email.trim())) {
    errors.email = 'Please enter a valid email address.'
    isValid = false
  } else if (form.email.length > 150) {
    errors.email = 'Email must not exceed 150 characters.'
    isValid = false
  }

  if (!form.body.trim()) {
    errors.body = 'Please enter your message.'
    isValid = false
  } else if (form.body.length > 3000) {
    errors.body = 'Message cannot exceed 3,000 characters.'
    isValid = false
  }

  return isValid
}

/**
 * Handle form submission
 */
async function handleSubmit() {
  if (!validate()) return

  isSubmitting.value = true
  serverError.value = ''

  try {
    const response = await sendMessage({
      name: form.name.trim(),
      email: form.email.trim(),
      body: form.body.trim(),
    })

    isSubmitted.value = true
    toast.success(response.message || 'Thank you! Your message has been sent.')

    // Reset form fields
    form.name = ''
    form.email = ''
    form.body = ''
  } catch (err) {
    if (err.status === 422 && err.data?.errors) {
      // Map Laravel validation errors to fields
      if (err.data.errors.name) errors.name = err.data.errors.name[0]
      if (err.data.errors.email) errors.email = err.data.errors.email[0]
      if (err.data.errors.body) errors.body = err.data.errors.body[0]
    } else if (err.status === 429) {
      serverError.value = 'Too many requests. Please wait a minute before trying again.'
      toast.warning('Rate limit reached. Please wait a minute.')
    } else {
      serverError.value = err.message || 'Failed to send message. Please try again later.'
      toast.error('Unable to send message.')
    }
  } finally {
    isSubmitting.value = false
  }
}

/**
 * Reset form to send another message
 */
function resetForm() {
  isSubmitted.value = false
  serverError.value = ''
}
</script>

<template>
  <section
    id="contact"
    aria-labelledby="contact-title"
    class="py-20"
  >
    <AppContainer class="space-y-12">
      <!-- Section Header -->
      <div class="space-y-2 max-w-xl">
        <span class="inline-block text-xs font-mono font-semibold uppercase tracking-wider text-accent">
          Get In Touch
        </span>
        <h2 id="contact-title" class="text-heading font-bold tracking-tight text-fg">
          Let's Work Together
        </h2>
        <p class="text-sm sm:text-base text-muted">
          Have an upcoming project, freelance inquiry, or architectural question? Send a message below.
        </p>
      </div>

      <!-- Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
        <!-- Left: Direct Details Card -->
        <div class="lg:col-span-5 space-y-6">
          <div class="rounded-card border border-line bg-surface p-6 sm:p-8 shadow-card hover-card space-y-6">
            <h3 class="text-lg font-bold text-fg">
              Contact Information
            </h3>

            <!-- Details List -->
            <div class="space-y-4">
              <!-- Location -->
              <div v-if="portfolioStore.profile?.location" class="flex items-start gap-3.5">
                <div class="w-9 h-9 rounded-control bg-accent-soft text-accent flex items-center justify-center shrink-0">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                    <circle cx="12" cy="10" r="3" />
                  </svg>
                </div>
                <div>
                  <span class="text-xs font-semibold text-muted uppercase tracking-wider block">Location</span>
                  <span class="text-sm font-medium text-fg">
                    {{ portfolioStore.profile.location }}
                  </span>
                </div>
              </div>

              <!-- Status -->
              <div v-if="portfolioStore.profile?.status" class="flex items-start gap-3.5">
                <div class="w-9 h-9 rounded-control bg-emerald-500/10 text-emerald-500 flex items-center justify-center shrink-0">
                  <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse" />
                </div>
                <div>
                  <span class="text-xs font-semibold text-muted uppercase tracking-wider block">Current Availability</span>
                  <span class="text-sm font-medium text-fg">
                    {{ portfolioStore.profile.status }}
                  </span>
                </div>
              </div>
            </div>

            <div class="pt-4 border-t border-line">
              <span class="text-xs font-semibold text-muted uppercase tracking-wider block mb-3">Contact & Social Profiles</span>
              <SocialLinks variant="buttons" />
            </div>
          </div>
        </div>

        <!-- Right: Interactive Contact Form -->
        <div class="lg:col-span-7">
          <div class="rounded-card border border-line bg-surface p-6 sm:p-8 shadow-card hover-card">
            <!-- Inline Success Card -->
            <div
              v-if="isSubmitted"
              class="py-10 text-center space-y-4"
              role="status"
            >
              <div class="w-14 h-14 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 flex items-center justify-center mx-auto">
                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12" />
                </svg>
              </div>

              <div class="space-y-1">
                <h3 class="text-xl font-bold text-fg">
                  Message Sent Successfully!
                </h3>
                <p class="text-sm text-muted max-w-md mx-auto">
                  Thank you for reaching out. I have received your message and will reply as soon as possible.
                </p>
              </div>

              <BaseButton
                variant="secondary"
                size="sm"
                class="mt-2"
                @click="resetForm"
              >
                Send Another Message
              </BaseButton>
            </div>

            <!-- The Form -->
            <form
              v-else
              class="space-y-5"
              novalidate
              @submit.prevent="handleSubmit"
            >
              <!-- Server Error Banner -->
              <div
                v-if="serverError"
                class="p-3.5 rounded-control bg-danger/10 border border-danger/30 text-danger text-sm"
                role="alert"
              >
                {{ serverError }}
              </div>

              <!-- Name Field -->
              <BaseInput
                id="contact-name"
                v-model="form.name"
                label="Your Name"
                placeholder="Jane Doe"
                :required="true"
                :error="errors.name"
              />

              <!-- Email Field -->
              <BaseInput
                id="contact-email"
                v-model="form.email"
                type="email"
                label="Email Address"
                placeholder="jane@example.com"
                :required="true"
                :error="errors.email"
              />

              <!-- Message Body Field -->
              <BaseTextarea
                id="contact-body"
                v-model="form.body"
                label="Message"
                placeholder="Hi! I'd like to talk about a potential project..."
                :rows="5"
                :maxlength="3000"
                :required="true"
                :error="errors.body"
              />

              <!-- Submit Button -->
              <div class="pt-2">
                <BaseButton
                  type="submit"
                  variant="primary"
                  size="md"
                  :loading="isSubmitting"
                  class="w-full sm:w-auto min-w-[160px]"
                >
                  <span>Send Message</span>
                  <svg class="w-4 h-4 ml-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13" />
                    <polygon points="22 2 15 22 11 13 2 9 22 2" />
                  </svg>
                </BaseButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </AppContainer>
  </section>
</template>
