<!--
  SocialsView.vue — Admin Social Links Manager
  ─────────────────────────────────────────────
  CRUD for social media links (GitHub, LinkedIn, etc.)
  - List all socials in a table
  - Add new via inline modal
  - Edit via modal
  - Delete with confirmation dialog
-->

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { getAdminSocials, createAdminSocial, updateAdminSocial, deleteAdminSocial } from '@/api/admin/socials.js'
import { useToast } from '@/composables/useToast.js'
import { usePortfolioStore } from '@/stores/portfolio.js'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue'

const toast = useToast()
const portfolioStore = usePortfolioStore()

const socials = ref([])
const isLoading = ref(true)
const loadError = ref('')

// Modal state
const modalOpen = ref(false)
const isEditing = ref(false)
const isSaving = ref(false)
const editingId = ref(null)

const form = reactive({ name: '', url: '', sort_order: 0 })
const errors = reactive({ name: '', url: '', sort_order: '' })

// Confirm delete
const confirmOpen = ref(false)
const isDeleting = ref(false)
const deleteTarget = ref(null)

async function load() {
  isLoading.value = true
  loadError.value = ''
  try {
    socials.value = await getAdminSocials()
  } catch (err) {
    loadError.value = err.message || 'Failed to load social links.'
  } finally {
    isLoading.value = false
  }
}

onMounted(load)

function openCreate() {
  isEditing.value = false
  editingId.value = null
  form.name = ''
  form.url = ''
  form.sort_order = (socials.value.length + 1)
  Object.keys(errors).forEach((k) => (errors[k] = ''))
  modalOpen.value = true
}

function openEdit(social) {
  isEditing.value = true
  editingId.value = social.id
  form.name = social.name
  form.url = social.url
  form.sort_order = social.sort_order
  Object.keys(errors).forEach((k) => (errors[k] = ''))
  modalOpen.value = true
}

function openDelete(social) {
  deleteTarget.value = social
  confirmOpen.value = true
}

async function handleSave() {
  Object.keys(errors).forEach((k) => (errors[k] = ''))
  isSaving.value = true

  try {
    const payload = { name: form.name, url: form.url, sort_order: Number(form.sort_order) }

    if (isEditing.value) {
      const updated = await updateAdminSocial(editingId.value, payload)
      const idx = socials.value.findIndex((s) => s.id === editingId.value)
      if (idx !== -1) socials.value[idx] = updated
      toast.success('Social link updated.')
    } else {
      const created = await createAdminSocial(payload)
      socials.value.push(created)
      toast.success('Social link added.')
    }
    modalOpen.value = false
    // Refresh the public portfolio store so social link changes appear
    portfolioStore.refresh()
  } catch (err) {
    if (err.fieldErrors) {
      Object.entries(err.fieldErrors).forEach(([f, msgs]) => {
        if (f in errors) errors[f] = msgs[0]
      })
    } else {
      toast.error(err.message || 'Save failed.')
    }
  } finally {
    isSaving.value = false
  }
}

async function handleDelete() {
  if (!deleteTarget.value) return
  isDeleting.value = true
  try {
    await deleteAdminSocial(deleteTarget.value.id)
    socials.value = socials.value.filter((s) => s.id !== deleteTarget.value.id)
    toast.success('Social link deleted.')
    confirmOpen.value = false
    // Refresh the public portfolio store so deleted link disappears
    portfolioStore.refresh()
  } catch (err) {
    toast.error(err.message || 'Delete failed.')
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <div class="max-w-3xl space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-fg">Social Links</h2>
        <p class="text-sm text-muted mt-1">Manage your social media profiles.</p>
      </div>
      <BaseButton variant="primary" size="md" @click="openCreate">
        <svg class="w-4 h-4 mr-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Link
      </BaseButton>
    </div>

    <!-- Error -->
    <div v-if="loadError" class="p-4 rounded-card bg-danger/10 border border-danger/30 text-danger text-sm">{{ loadError }}</div>

    <!-- Loading -->
    <div v-else-if="isLoading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="h-16 skeleton rounded-card" />
    </div>

    <!-- Empty -->
    <div v-else-if="socials.length === 0" class="text-center py-16 text-muted">
      <p class="text-lg font-medium">No social links yet</p>
      <p class="text-sm mt-1">Add your first social profile link.</p>
    </div>

    <!-- Table -->
    <div v-else class="bg-surface border border-line rounded-card shadow-card overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-line">
            <th class="text-left px-5 py-3 text-xs font-semibold text-muted uppercase tracking-wider">#</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-muted uppercase tracking-wider">Name</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-muted uppercase tracking-wider hidden sm:table-cell">URL</th>
            <th class="px-5 py-3" />
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="social in socials"
            :key="social.id"
            class="border-b border-line last:border-0 hover:bg-surface-2 transition-colors"
          >
            <td class="px-5 py-3.5 text-muted text-xs">{{ social.sort_order }}</td>
            <td class="px-5 py-3.5 font-medium text-fg">{{ social.name }}</td>
            <td class="px-5 py-3.5 text-muted hidden sm:table-cell max-w-xs">
              <a :href="social.url" target="_blank" rel="noopener" class="hover:text-accent transition-colors truncate block">{{ social.url }}</a>
            </td>
            <td class="px-5 py-3.5">
              <div class="flex items-center justify-end gap-2">
                <BaseButton variant="ghost" size="sm" @click="openEdit(social)">Edit</BaseButton>
                <BaseButton variant="ghost" size="sm" class="text-danger hover:text-danger" @click="openDelete(social)">Delete</BaseButton>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create / Edit Modal -->
    <BaseModal
      v-model:open="modalOpen"
      :title="isEditing ? 'Edit Social Link' : 'Add Social Link'"
      max-width="max-w-md"
    >
      <div class="space-y-4">
        <!-- Name -->
        <div class="space-y-1.5">
          <label for="social-name" class="block text-sm font-medium text-fg">Platform Name <span class="text-danger">*</span></label>
          <input id="social-name" v-model="form.name" type="text" maxlength="50" placeholder="GitHub" class="input-field w-full" :class="errors.name ? 'border-danger' : ''" />
          <p v-if="errors.name" class="text-xs text-danger">{{ errors.name }}</p>
        </div>

        <!-- URL -->
        <div class="space-y-1.5">
          <label for="social-url" class="block text-sm font-medium text-fg">URL <span class="text-danger">*</span></label>
          <input id="social-url" v-model="form.url" type="url" maxlength="255" placeholder="https://github.com/username" class="input-field w-full" :class="errors.url ? 'border-danger' : ''" />
          <p v-if="errors.url" class="text-xs text-danger">{{ errors.url }}</p>
        </div>

        <!-- Sort order -->
        <div class="space-y-1.5">
          <label for="social-order" class="block text-sm font-medium text-fg">Sort Order</label>
          <input id="social-order" v-model="form.sort_order" type="number" min="0" class="input-field w-24" />
        </div>
      </div>

      <template #footer>
        <BaseButton variant="secondary" size="sm" @click="modalOpen = false">Cancel</BaseButton>
        <BaseButton variant="primary" size="sm" :loading="isSaving" @click="handleSave">
          {{ isEditing ? 'Update' : 'Create' }}
        </BaseButton>
      </template>
    </BaseModal>

    <!-- Delete Confirm -->
    <ConfirmDialog
      v-model:open="confirmOpen"
      title="Delete Social Link"
      :message="`'${deleteTarget?.name}' will be permanently deleted.`"
      :loading="isDeleting"
      @confirm="handleDelete"
    />
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
