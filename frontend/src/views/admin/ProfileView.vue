<!--
  ProfileView.vue — Admin Profile Editor
  ───────────────────────────────────────
  Edit all profile fields: name, initials, headline, bio, email,
  location, status, and avatar (via ImageUpload).
  Saves via PUT /admin/profile.
-->

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { getAdminProfile, updateAdminProfile } from '@/api/admin/profile.js'
import { useToast } from '@/composables/useToast.js'
import { usePortfolioStore } from '@/stores/portfolio.js'
import BaseButton from '@/components/ui/BaseButton.vue'
import ImageUpload from '@/components/admin/ImageUpload.vue'

const toast = useToast()
const portfolioStore = usePortfolioStore()

const form = reactive({
  name: '',
  initials: '',
  headline: '',
  bio: '',
  email: '',
  location: '',
  status: '',
  avatar: null,
})

const errors = reactive({
  name: '', initials: '', headline: '', bio: '', email: '',
  location: '', status: '', avatar: '',
})

const isLoading = ref(true)
const isSaving = ref(false)
const loadError = ref('')

onMounted(async () => {
  try {
    const data = await getAdminProfile()
    Object.assign(form, {
      name: data.name || '',
      initials: data.initials || '',
      headline: data.headline || '',
      bio: data.bio || '',
      email: data.email || '',
      location: data.location || '',
      status: data.status || '',
      avatar: data.avatar || null,
    })
  } catch (err) {
    loadError.value = err.message || 'Failed to load profile.'
  } finally {
    isLoading.value = false
  }
})

function clearErrors() {
  Object.keys(errors).forEach((k) => (errors[k] = ''))
}

async function handleSave() {
  clearErrors()
  isSaving.value = true

  try {
    await updateAdminProfile({ ...form })
    toast.success('Profile updated successfully.')
    // Refresh the public portfolio store so the visitor sees the latest profile data
    portfolioStore.refresh()
  } catch (err) {
    if (err.fieldErrors) {
      Object.entries(err.fieldErrors).forEach(([field, msgs]) => {
        if (field in errors) errors[field] = msgs[0]
      })
    } else {
      toast.error(err.message || 'Failed to save profile.')
    }
  } finally {
    isSaving.value = false
  }
}

function field(id, label, type = 'text', placeholder = '') {
  return { id, label, type, placeholder }
}
</script>

<template>
  <div class="max-w-2xl space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-fg">Profile</h2>
        <p class="text-sm text-muted mt-1">Edit your public portfolio information.</p>
      </div>
      <BaseButton variant="primary" size="md" :loading="isSaving" @click="handleSave">
        Save Changes
      </BaseButton>
    </div>

    <!-- Error state -->
    <div v-if="loadError" class="p-4 rounded-card bg-danger/10 border border-danger/30 text-danger text-sm">
      {{ loadError }}
    </div>

    <!-- Loading skeleton -->
    <div v-else-if="isLoading" class="space-y-5">
      <div v-for="i in 6" :key="i" class="h-14 skeleton rounded-control" />
    </div>

    <!-- Form -->
    <form v-else class="space-y-6" novalidate @submit.prevent="handleSave">
      <!-- Avatar -->
      <div class="bg-surface border border-line rounded-card p-6 shadow-card space-y-4">
        <h3 class="text-sm font-semibold text-fg">Avatar</h3>
        <ImageUpload v-model="form.avatar" label="Profile Photo" />
        <p v-if="errors.avatar" class="text-xs text-danger">{{ errors.avatar }}</p>
      </div>

      <!-- Basic Info -->
      <div class="bg-surface border border-line rounded-card p-6 shadow-card space-y-4">
        <h3 class="text-sm font-semibold text-fg">Basic Info</h3>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <!-- Name (takes 2 cols) -->
          <div class="sm:col-span-2 space-y-1.5">
            <label for="profile-name" class="block text-sm font-medium text-fg">Full Name <span class="text-danger">*</span></label>
            <input
              id="profile-name"
              v-model="form.name"
              type="text"
              maxlength="100"
              placeholder="Jane Doe"
              class="input-field w-full"
              :class="errors.name ? 'border-danger' : ''"
            />
            <p v-if="errors.name" class="text-xs text-danger">{{ errors.name }}</p>
          </div>

          <!-- Initials -->
          <div class="space-y-1.5">
            <label for="profile-initials" class="block text-sm font-medium text-fg">Initials <span class="text-danger">*</span></label>
            <input
              id="profile-initials"
              v-model="form.initials"
              type="text"
              maxlength="4"
              placeholder="JD"
              class="input-field w-full"
              :class="errors.initials ? 'border-danger' : ''"
            />
            <p v-if="errors.initials" class="text-xs text-danger">{{ errors.initials }}</p>
          </div>
        </div>

        <!-- Headline -->
        <div class="space-y-1.5">
          <label for="profile-headline" class="block text-sm font-medium text-fg">Headline <span class="text-danger">*</span></label>
          <input
            id="profile-headline"
            v-model="form.headline"
            type="text"
            maxlength="150"
            placeholder="Full-Stack Developer | Laravel & Vue.js Specialist"
            class="input-field w-full"
            :class="errors.headline ? 'border-danger' : ''"
          />
          <p v-if="errors.headline" class="text-xs text-danger">{{ errors.headline }}</p>
        </div>

        <!-- Bio -->
        <div class="space-y-1.5">
          <label for="profile-bio" class="block text-sm font-medium text-fg">Bio <span class="text-danger">*</span></label>
          <textarea
            id="profile-bio"
            v-model="form.bio"
            rows="4"
            maxlength="2000"
            placeholder="Write a brief bio about yourself..."
            class="input-field w-full resize-none"
            :class="errors.bio ? 'border-danger' : ''"
          />
          <div class="flex justify-between">
            <p v-if="errors.bio" class="text-xs text-danger">{{ errors.bio }}</p>
            <p class="text-xs text-muted ml-auto">{{ form.bio.length }}/2000</p>
          </div>
        </div>
      </div>

      <!-- Contact & Status -->
      <div class="bg-surface border border-line rounded-card p-6 shadow-card space-y-4">
        <h3 class="text-sm font-semibold text-fg">Contact & Status</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Email -->
          <div class="space-y-1.5">
            <label for="profile-email" class="block text-sm font-medium text-fg">Email <span class="text-danger">*</span></label>
            <input
              id="profile-email"
              v-model="form.email"
              type="email"
              maxlength="150"
              placeholder="jane@example.com"
              class="input-field w-full"
              :class="errors.email ? 'border-danger' : ''"
            />
            <p v-if="errors.email" class="text-xs text-danger">{{ errors.email }}</p>
          </div>

          <!-- Location -->
          <div class="space-y-1.5">
            <label for="profile-location" class="block text-sm font-medium text-fg">Location</label>
            <input
              id="profile-location"
              v-model="form.location"
              type="text"
              maxlength="100"
              placeholder="Vilnius, Lithuania"
              class="input-field w-full"
            />
          </div>
        </div>

        <!-- Availability status -->
        <div class="space-y-1.5">
          <label for="profile-status" class="block text-sm font-medium text-fg">Availability Status</label>
          <input
            id="profile-status"
            v-model="form.status"
            type="text"
            maxlength="100"
            placeholder="Available for freelance work"
            class="input-field w-full"
          />
          <p class="text-xs text-muted">Shown on the contact section.</p>
        </div>
      </div>

      <!-- Submit -->
      <div class="flex justify-end">
        <BaseButton type="submit" variant="primary" size="md" :loading="isSaving">
          Save Changes
        </BaseButton>
      </div>
    </form>
  </div>
</template>

<style scoped>
@reference "@/assets/main.css";

.input-field {
  @apply px-3.5 py-2.5 rounded-control border border-line bg-surface text-sm text-fg
         placeholder:text-muted transition-colors outline-none
         focus:ring-2 focus:ring-accent focus:border-accent;
}
</style>
