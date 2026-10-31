<!--
  ExperienceView.vue — Admin Experience Manager
  ──────────────────────────────────────────────
  CRUD for work experience entries.
  - List all experiences
  - Add / Edit via modal
  - Delete with confirmation
-->

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { getAdminExperiences, createAdminExperience, updateAdminExperience, deleteAdminExperience } from '@/api/admin/experiences.js'
import { useToast } from '@/composables/useToast.js'
import { usePortfolioStore } from '@/stores/portfolio.js'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue'

const toast = useToast()
const portfolioStore = usePortfolioStore()

const experiences = ref([])
const isLoading = ref(true)
const loadError = ref('')

// Modal
const modalOpen = ref(false)
const isEditing = ref(false)
const isSaving = ref(false)
const editingId = ref(null)

const form = reactive({ role: '', organization: '', period: '', description: '', sort_order: 0 })
const errors = reactive({ role: '', organization: '', period: '', description: '', sort_order: '' })

// Delete confirm
const confirmOpen = ref(false)
const isDeleting = ref(false)
const deleteTarget = ref(null)

async function load() {
  isLoading.value = true
  loadError.value = ''
  try {
    experiences.value = await getAdminExperiences()
  } catch (err) {
    loadError.value = err.message || 'Failed to load experiences.'
  } finally {
    isLoading.value = false
  }
}

onMounted(load)

function openCreate() {
  isEditing.value = false
  editingId.value = null
  form.role = ''
  form.organization = ''
  form.period = ''
  form.description = ''
  form.sort_order = experiences.value.length + 1
  Object.keys(errors).forEach((k) => (errors[k] = ''))
  modalOpen.value = true
}

function openEdit(exp) {
  isEditing.value = true
  editingId.value = exp.id
  form.role = exp.role
  form.organization = exp.organization
  form.period = exp.period
  form.description = exp.description || ''
  form.sort_order = exp.sort_order
  Object.keys(errors).forEach((k) => (errors[k] = ''))
  modalOpen.value = true
}

function openDelete(exp) {
  deleteTarget.value = exp
  confirmOpen.value = true
}

async function handleSave() {
  Object.keys(errors).forEach((k) => (errors[k] = ''))
  isSaving.value = true

  try {
    const payload = {
      role: form.role.trim(),
      organization: form.organization.trim(),
      period: form.period.trim(),
      description: form.description.trim() || null,
      sort_order: Number(form.sort_order),
    }

    if (isEditing.value) {
      const updated = await updateAdminExperience(editingId.value, payload)
      const idx = experiences.value.findIndex((e) => e.id === editingId.value)
      if (idx !== -1) experiences.value[idx] = updated
      toast.success('Experience updated.')
    } else {
      const created = await createAdminExperience(payload)
      experiences.value.push(created)
      toast.success('Experience added.')
    }
    modalOpen.value = false
    // Refresh the public portfolio store so the experience change is visible
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
    await deleteAdminExperience(deleteTarget.value.id)
    experiences.value = experiences.value.filter((e) => e.id !== deleteTarget.value.id)
    toast.success('Experience deleted.')
    confirmOpen.value = false
    // Refresh the public portfolio store so deleted experience disappears
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
        <h2 class="text-2xl font-bold text-fg">Experience</h2>
        <p class="text-sm text-muted mt-1">Manage your work history and career milestones.</p>
      </div>
      <BaseButton variant="primary" size="md" @click="openCreate">
        <svg class="w-4 h-4 mr-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Experience
      </BaseButton>
    </div>

    <!-- Error -->
    <div v-if="loadError" class="p-4 rounded-card bg-danger/10 border border-danger/30 text-danger text-sm">{{ loadError }}</div>

    <!-- Loading -->
    <div v-else-if="isLoading" class="space-y-3">
      <div v-for="i in 3" :key="i" class="h-24 skeleton rounded-card" />
    </div>

    <!-- Empty -->
    <div v-else-if="experiences.length === 0" class="text-center py-16 text-muted">
      <p class="text-lg font-medium">No experiences yet</p>
      <p class="text-sm mt-1">Add your first work experience above.</p>
    </div>

    <!-- List -->
    <div v-else class="space-y-3">
      <div
        v-for="exp in experiences"
        :key="exp.id"
        class="bg-surface border border-line rounded-card shadow-card p-5 flex items-start gap-4"
      >
        <div class="w-10 h-10 rounded-control bg-accent-soft text-accent flex items-center justify-center shrink-0 font-bold text-sm">
          {{ exp.sort_order }}
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
              <p class="font-semibold text-fg">{{ exp.role }}</p>
              <p class="text-sm text-accent font-medium">{{ exp.organization }}</p>
            </div>
            <span class="text-xs text-muted bg-surface-2 border border-line px-2.5 py-1 rounded-full shrink-0">{{ exp.period }}</span>
          </div>
          <p v-if="exp.description" class="text-sm text-muted mt-2 line-clamp-2">{{ exp.description }}</p>
        </div>
        <div class="flex flex-col sm:flex-row items-end sm:items-center gap-2 shrink-0">
          <BaseButton variant="ghost" size="sm" @click="openEdit(exp)">Edit</BaseButton>
          <BaseButton variant="ghost" size="sm" class="text-danger hover:text-danger" @click="openDelete(exp)">Delete</BaseButton>
        </div>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <BaseModal v-model:open="modalOpen" :title="isEditing ? 'Edit Experience' : 'Add Experience'" max-width="max-w-lg">
      <div class="space-y-4">
        <!-- Role -->
        <div class="space-y-1.5">
          <label for="exp-role" class="block text-sm font-medium text-fg">Role / Title <span class="text-danger">*</span></label>
          <input id="exp-role" v-model="form.role" type="text" maxlength="120" placeholder="Senior Full-Stack Developer" class="input-field w-full" :class="errors.role ? 'border-danger' : ''" />
          <p v-if="errors.role" class="text-xs text-danger">{{ errors.role }}</p>
        </div>

        <!-- Organization -->
        <div class="space-y-1.5">
          <label for="exp-org" class="block text-sm font-medium text-fg">Organization <span class="text-danger">*</span></label>
          <input id="exp-org" v-model="form.organization" type="text" maxlength="120" placeholder="Acme Corporation" class="input-field w-full" :class="errors.organization ? 'border-danger' : ''" />
          <p v-if="errors.organization" class="text-xs text-danger">{{ errors.organization }}</p>
        </div>

        <!-- Period -->
        <div class="space-y-1.5">
          <label for="exp-period" class="block text-sm font-medium text-fg">Period <span class="text-danger">*</span></label>
          <input id="exp-period" v-model="form.period" type="text" maxlength="50" placeholder="2022 - Present" class="input-field w-full" :class="errors.period ? 'border-danger' : ''" />
          <p v-if="errors.period" class="text-xs text-danger">{{ errors.period }}</p>
        </div>

        <!-- Description -->
        <div class="space-y-1.5">
          <label for="exp-desc" class="block text-sm font-medium text-fg">Description</label>
          <textarea id="exp-desc" v-model="form.description" rows="4" maxlength="1500" placeholder="Describe your responsibilities and achievements..." class="input-field w-full resize-none" />
          <p class="text-xs text-muted text-right">{{ form.description.length }}/1500</p>
        </div>

        <!-- Sort order -->
        <div class="space-y-1.5">
          <label for="exp-order" class="block text-sm font-medium text-fg">Sort Order</label>
          <input id="exp-order" v-model="form.sort_order" type="number" min="0" class="input-field w-24" />
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
      title="Delete Experience"
      :message="`'${deleteTarget?.role} at ${deleteTarget?.organization}' will be permanently deleted.`"
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
