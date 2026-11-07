<!--
  CertificationsView.vue — Admin Achievements Manager
  ──────────────────────────────────────────────────────
  Manage achievement records, publication status, and display order.
-->

<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { ArrowDown, ArrowUp, Pencil, Plus, Trash2 } from 'lucide-vue-next'
import {
  createAdminCertification,
  deleteAdminCertification,
  getAdminCertifications,
  reorderAdminCertifications,
  updateAdminCertification,
} from '@/api/admin/certifications.js'
import { useToast } from '@/composables/useToast.js'
import { useDocumentTitle } from '@/composables/useDocumentTitle.js'
import { usePortfolioStore } from '@/stores/portfolio.js'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue'
import ImageUpload from '@/components/admin/ImageUpload.vue'

useDocumentTitle('Achievements')

const toast = useToast()
const portfolioStore = usePortfolioStore()

const certifications = ref([])
const isLoading = ref(true)
const loadError = ref('')
const failedIcons = ref(new Set())
const failedPreviewIcon = ref(false)
const changingId = ref(null)
const isReordering = ref(false)

const modalOpen = ref(false)
const isEditing = ref(false)
const isSaving = ref(false)
const editingId = ref(null)

function localDateValue(date = new Date()) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function dateInputValue(value) {
  return value ? String(value).slice(0, 10) : ''
}

function emptyForm() {
  return {
    title: '',
    issuer: '',
    issuer_icon: '',
    issue_date: localDateValue(),
    expiry_date: '',
    no_expiry: true,
    credential_id: '',
    credential_url: '',
    image: null,
    description: '',
    is_published: true,
  }
}

const form = reactive(emptyForm())
const errors = reactive({
  title: '',
  issuer: '',
  issuer_icon: '',
  issue_date: '',
  expiry_date: '',
  credential_id: '',
  credential_url: '',
  image: '',
  description: '',
  is_published: '',
})

watch(() => form.issuer_icon, () => {
  failedPreviewIcon.value = false
})

const confirmOpen = ref(false)
const isDeleting = ref(false)
const deleteTarget = ref(null)

async function load() {
  isLoading.value = true
  loadError.value = ''
  try {
    certifications.value = await getAdminCertifications()
    failedIcons.value = new Set()
  } catch (err) {
    loadError.value = err.message || 'Failed to load achievements.'
  } finally {
    isLoading.value = false
  }
}

onMounted(load)

function clearErrors() {
  Object.keys(errors).forEach((field) => {
    errors[field] = ''
  })
}

function openCreate() {
  isEditing.value = false
  editingId.value = null
  Object.assign(form, emptyForm(), { is_published: true })
  clearErrors()
  failedPreviewIcon.value = false
  modalOpen.value = true
}

function openEdit(certification) {
  isEditing.value = true
  editingId.value = certification.id
  Object.assign(form, {
    title: certification.title || '',
    issuer: certification.issuer || '',
    issuer_icon: certification.issuer_icon || '',
    issue_date: dateInputValue(certification.issue_date),
    expiry_date: dateInputValue(certification.expiry_date),
    no_expiry: !certification.expiry_date,
    credential_id: certification.credential_id || '',
    credential_url: certification.credential_url || '',
    image: certification.image || null,
    description: certification.description || '',
    is_published: !!certification.is_published,
  })
  clearErrors()
  failedPreviewIcon.value = false
  modalOpen.value = true
}

function openDelete(certification) {
  deleteTarget.value = certification
  confirmOpen.value = true
}

function formPayload() {
  return {
    title: form.title.trim(),
    issuer: form.issuer.trim(),
    issuer_icon: form.issuer_icon.trim() || null,
    issue_date: form.issue_date,
    expiry_date: form.no_expiry ? null : form.expiry_date || null,
    credential_id: form.credential_id.trim() || null,
    credential_url: form.credential_url.trim() || null,
    image: form.image || null,
    description: form.description.trim() || null,
    is_published: form.is_published,
  }
}

async function refreshPublicData() {
  await portfolioStore.refresh()
}

async function handleSave() {
  clearErrors()
  isSaving.value = true

  try {
    if (isEditing.value) {
      await updateAdminCertification(editingId.value, formPayload())
      toast.success('Achievement updated.')
    } else {
      await createAdminCertification(formPayload())
      toast.success('Achievement added.')
    }

    modalOpen.value = false
    await Promise.all([load(), refreshPublicData()])
  } catch (err) {
    if (err.fieldErrors) {
      Object.entries(err.fieldErrors).forEach(([field, messages]) => {
        if (field in errors) errors[field] = messages[0]
      })
    } else {
      toast.error(err.message || 'Save failed.')
    }
  } finally {
    isSaving.value = false
  }
}

async function togglePublished(certification) {
  changingId.value = certification.id
  try {
    await updateAdminCertification(certification.id, {
      title: certification.title,
      issuer: certification.issuer,
      issuer_icon: certification.issuer_icon || null,
      issue_date: dateInputValue(certification.issue_date),
      expiry_date: dateInputValue(certification.expiry_date) || null,
      credential_id: certification.credential_id || null,
      credential_url: certification.credential_url || null,
      image: certification.image || null,
      description: certification.description || null,
      is_published: !certification.is_published,
      sort_order: certification.sort_order,
    })
    await Promise.all([load(), refreshPublicData()])
    toast.success(certification.is_published ? 'Achievement unpublished.' : 'Achievement published.')
  } catch (err) {
    toast.error(err.message || 'Publication status could not be updated.')
  } finally {
    changingId.value = null
  }
}

async function moveCertification(index, direction) {
  const targetIndex = index + direction
  if (targetIndex < 0 || targetIndex >= certifications.value.length || isReordering.value) return

  const reordered = [...certifications.value]
  ;[reordered[index], reordered[targetIndex]] = [reordered[targetIndex], reordered[index]]

  isReordering.value = true
  try {
    certifications.value = await reorderAdminCertifications(
      reordered.map((certification, sortOrder) => ({ id: certification.id, sort_order: sortOrder })),
    )
    await refreshPublicData()
    toast.success('Achievement order saved.')
  } catch (err) {
    toast.error(err.message || 'Order could not be saved.')
    await load()
  } finally {
    isReordering.value = false
  }
}

async function handleDelete() {
  if (!deleteTarget.value) return

  isDeleting.value = true
  try {
    await deleteAdminCertification(deleteTarget.value.id)
    toast.success('Achievement deleted.')
    confirmOpen.value = false
    await Promise.all([load(), refreshPublicData()])
  } catch (err) {
    toast.error(err.message || 'Delete failed.')
  } finally {
    isDeleting.value = false
  }
}

function formatDate(value) {
  const date = dateInputValue(value)
  if (!date) return ''

  return new Intl.DateTimeFormat(undefined, { month: 'short', year: 'numeric' })
    .format(new Date(`${date}T00:00:00`))
}

function markIconFailed(id) {
  failedIcons.value.add(id)
}
</script>

<template>
  <div class="max-w-5xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-fg">Achievements</h2>
        <p class="mt-1 text-sm text-muted">
          {{ certifications.length }} achievement{{ certifications.length === 1 ? '' : 's' }} total.
        </p>
      </div>
      <BaseButton variant="primary" size="md" @click="openCreate">
        <Plus class="mr-1.5 h-4 w-4" aria-hidden="true" />
        New Achievement
      </BaseButton>
    </div>

    <div v-if="loadError" class="rounded-card border border-danger/30 bg-danger/10 p-4 text-sm text-danger">
      {{ loadError }}
      <button type="button" class="ml-2 underline" @click="load">Retry</button>
    </div>

    <ul v-else-if="isLoading" class="space-y-3" aria-label="Loading achievements">
      <li v-for="item in 5" :key="item" class="h-24 skeleton rounded-card" />
    </ul>

    <div v-else-if="certifications.length === 0" class="py-16 text-center text-muted">
      <p class="text-lg font-medium">No achievements yet</p>
      <p class="mt-1 text-sm">Add an achievement to display it on your portfolio.</p>
    </div>

    <ul v-else class="space-y-3" aria-label="Achievements">
      <li
        v-for="(certification, index) in certifications"
        :key="certification.id"
        class="grid grid-cols-1 gap-4 rounded-card border border-line bg-surface p-4 shadow-card sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"
      >
        <div class="flex min-w-0 items-start gap-3">
          <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-line bg-white">
            <Icon
              v-if="!failedIcons.has(certification.id)"
              :icon="certification.issuer_icon || 'lucide:award'"
              width="24"
              height="24"
              aria-hidden="true"
              @error="markIconFailed(certification.id)"
            />
            <Icon v-else icon="lucide:award" width="24" height="24" aria-hidden="true" />
          </span>

          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
              <h3 class="font-semibold leading-snug text-fg">{{ certification.title }}</h3>
              <span
                class="rounded-full px-2 py-0.5 text-xs font-semibold"
                :class="certification.is_published ? 'bg-success/10 text-success' : 'border border-line bg-surface-2 text-muted'"
              >
                {{ certification.is_published ? 'Published' : 'Draft' }}
              </span>
            </div>
            <p class="mt-0.5 truncate text-sm text-muted">{{ certification.issuer }}</p>
            <p class="mt-1 text-xs text-muted">
              Issued {{ formatDate(certification.issue_date) }}
              <span v-if="certification.expiry_date"> · Expires {{ formatDate(certification.expiry_date) }}</span>
              <span v-else> · No expiry</span>
            </p>
          </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-line pt-3 sm:border-0 sm:pt-0">
          <button
            type="button"
            role="switch"
            :aria-checked="certification.is_published"
            :aria-label="`${certification.is_published ? 'Unpublish' : 'Publish'} ${certification.title}`"
            :disabled="changingId === certification.id"
            class="min-h-11 rounded-control border border-line px-3 text-xs font-medium text-fg transition-colors hover:bg-surface-2 focus-ring disabled:cursor-wait disabled:opacity-60"
            @click="togglePublished(certification)"
          >
            {{ certification.is_published ? 'Published' : 'Draft' }}
          </button>

          <div class="flex items-center gap-1" aria-label="Reorder achievement">
            <button
              type="button"
              :disabled="index === 0 || isReordering"
              class="min-h-11 min-w-11 rounded-control text-muted hover-icon focus-ring disabled:cursor-not-allowed disabled:opacity-40"
              :aria-label="`Move ${certification.title} up`"
              title="Move up"
              @click="moveCertification(index, -1)"
            >
              <ArrowUp class="mx-auto h-4 w-4" aria-hidden="true" />
            </button>
            <button
              type="button"
              :disabled="index === certifications.length - 1 || isReordering"
              class="min-h-11 min-w-11 rounded-control text-muted hover-icon focus-ring disabled:cursor-not-allowed disabled:opacity-40"
              :aria-label="`Move ${certification.title} down`"
              title="Move down"
              @click="moveCertification(index, 1)"
            >
              <ArrowDown class="mx-auto h-4 w-4" aria-hidden="true" />
            </button>
          </div>

          <BaseButton variant="ghost" size="sm" :aria-label="`Edit ${certification.title}`" @click="openEdit(certification)">
            <Pencil class="mr-1.5 h-4 w-4" aria-hidden="true" />
            Edit
          </BaseButton>
          <BaseButton
            variant="ghost"
            size="sm"
            class="text-danger hover:text-danger"
            :aria-label="`Delete ${certification.title}`"
            @click="openDelete(certification)"
          >
            <Trash2 class="mr-1.5 h-4 w-4" aria-hidden="true" />
            Delete
          </BaseButton>
        </div>
      </li>
    </ul>

    <BaseModal
      v-model:open="modalOpen"
      :title="isEditing ? 'Edit Achievement' : 'New Achievement'"
      max-width="max-w-2xl"
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="space-y-1.5">
          <label for="cert-title" class="block text-sm font-medium text-fg">Title <span class="text-danger">*</span></label>
          <input id="cert-title" v-model="form.title" type="text" maxlength="150" class="input-field w-full" :class="errors.title ? 'border-danger' : ''" />
          <p v-if="errors.title" class="text-xs text-danger">{{ errors.title }}</p>
        </div>

        <div class="space-y-1.5">
          <label for="cert-issuer" class="block text-sm font-medium text-fg">Issuer <span class="text-danger">*</span></label>
          <input id="cert-issuer" v-model="form.issuer" type="text" maxlength="120" class="input-field w-full" :class="errors.issuer ? 'border-danger' : ''" />
          <p v-if="errors.issuer" class="text-xs text-danger">{{ errors.issuer }}</p>
        </div>

        <div class="space-y-1.5 sm:col-span-2">
          <label for="cert-icon" class="block text-sm font-medium text-fg">Issuer icon</label>
          <div class="flex items-center gap-3">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-line bg-white">
              <Icon
                v-if="!failedPreviewIcon"
                :icon="form.issuer_icon.trim() || 'lucide:award'"
                width="28"
                height="28"
                aria-hidden="true"
                @error="failedPreviewIcon = true"
              />
              <Icon v-else icon="lucide:award" width="28" height="28" aria-hidden="true" />
            </span>
            <input id="cert-icon" v-model="form.issuer_icon" type="text" maxlength="80" placeholder="simple-icons:coursera" class="input-field min-w-0 flex-1" :class="errors.issuer_icon ? 'border-danger' : ''" />
          </div>
          <p v-if="errors.issuer_icon" class="text-xs text-danger">{{ errors.issuer_icon }}</p>
          <p class="text-xs text-muted">
            Find the name on
            <a href="https://icon-sets.iconify.design/" target="_blank" rel="noopener noreferrer" class="underline hover:text-accent">icon-sets.iconify.design</a>.
            Leave empty for a plain award icon.
          </p>
        </div>

        <div class="space-y-1.5">
          <label for="cert-issue-date" class="block text-sm font-medium text-fg">Issue date <span class="text-danger">*</span></label>
          <input id="cert-issue-date" v-model="form.issue_date" type="date" :max="localDateValue()" class="input-field w-full" :class="errors.issue_date ? 'border-danger' : ''" />
          <p v-if="errors.issue_date" class="text-xs text-danger">{{ errors.issue_date }}</p>
        </div>

        <div class="space-y-1.5">
          <label for="cert-expiry-date" class="block text-sm font-medium text-fg">Expiry date</label>
          <input id="cert-expiry-date" v-model="form.expiry_date" type="date" :disabled="form.no_expiry" :min="form.issue_date || undefined" class="input-field w-full disabled:cursor-not-allowed disabled:opacity-60" :class="errors.expiry_date ? 'border-danger' : ''" />
          <label class="inline-flex min-h-11 items-center gap-2 text-sm text-muted">
            <input v-model="form.no_expiry" type="checkbox" class="h-4 w-4 accent-accent" @change="form.no_expiry && (form.expiry_date = '')" />
            No expiry
          </label>
          <p v-if="errors.expiry_date" class="text-xs text-danger">{{ errors.expiry_date }}</p>
        </div>

        <div class="space-y-1.5">
          <label for="cert-credential-id" class="block text-sm font-medium text-fg">Credential ID</label>
          <input id="cert-credential-id" v-model="form.credential_id" type="text" maxlength="100" class="input-field w-full" :class="errors.credential_id ? 'border-danger' : ''" />
          <p v-if="errors.credential_id" class="text-xs text-danger">{{ errors.credential_id }}</p>
        </div>

        <div class="space-y-1.5">
          <label for="cert-credential-url" class="block text-sm font-medium text-fg">Credential URL</label>
          <input id="cert-credential-url" v-model="form.credential_url" type="url" maxlength="255" placeholder="https://..." class="input-field w-full" :class="errors.credential_url ? 'border-danger' : ''" />
          <p v-if="errors.credential_url" class="text-xs text-danger">{{ errors.credential_url }}</p>
        </div>

        <div class="space-y-1.5 sm:col-span-2">
          <ImageUpload v-model="form.image" label="Certificate image" />
          <p v-if="errors.image" class="text-xs text-danger">{{ errors.image }}</p>
        </div>

        <div class="space-y-1.5 sm:col-span-2">
          <label for="cert-description" class="block text-sm font-medium text-fg">Short description</label>
          <textarea id="cert-description" v-model="form.description" rows="3" maxlength="500" class="input-field w-full resize-y" :class="errors.description ? 'border-danger' : ''" />
          <div class="flex justify-between gap-3">
            <p v-if="errors.description" class="text-xs text-danger">{{ errors.description }}</p>
            <p class="ml-auto text-xs text-muted">{{ form.description.length }}/500</p>
          </div>
        </div>

        <div class="flex items-center justify-between rounded-card border border-line bg-surface-2 p-4 sm:col-span-2">
          <div>
            <p class="text-sm font-semibold text-fg">Published</p>
            <p class="text-xs text-muted">Only published achievements appear publicly.</p>
            <p v-if="errors.is_published" class="mt-1 text-xs text-danger">{{ errors.is_published }}</p>
          </div>
          <button
            type="button"
            role="switch"
            :aria-checked="form.is_published"
            aria-label="Toggle published status"
            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-bg"
            :class="form.is_published ? 'bg-accent' : 'bg-line'"
            @click="form.is_published = !form.is_published"
          >
            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform" :class="form.is_published ? 'translate-x-6' : 'translate-x-1'" />
          </button>
        </div>
      </div>

      <template #footer>
        <BaseButton variant="secondary" size="sm" @click="modalOpen = false">Cancel</BaseButton>
        <BaseButton variant="primary" size="sm" :loading="isSaving" @click="handleSave">
          {{ isEditing ? 'Update Achievement' : 'Create Achievement' }}
        </BaseButton>
      </template>
    </BaseModal>

    <ConfirmDialog
      v-model:open="confirmOpen"
      title="Delete Achievement"
      :message="`'${deleteTarget?.title}' will be permanently deleted along with its unused image.`"
      :loading="isDeleting"
      @confirm="handleDelete"
    />
  </div>
</template>

<style scoped>
@reference "@/assets/main.css";

.input-field {
  @apply min-h-11 px-3.5 py-2.5 rounded-control border border-line bg-surface text-sm text-fg
         placeholder:text-muted transition-colors outline-none
         focus:ring-2 focus:ring-accent focus:border-accent;
}
</style>