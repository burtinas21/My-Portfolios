<!--
  ExperienceView.vue — Admin Experience Manager
  ──────────────────────────────────────────────
  CRUD for work experience entries.
  - List all experiences
  - Add / Edit via modal
  - Delete with confirmation
-->

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { getAdminExperiences, createAdminExperience, updateAdminExperience, deleteAdminExperience } from '@/api/admin/experiences.js'
import { useToast } from '@/composables/useToast.js'
import { useDocumentTitle } from '@/composables/useDocumentTitle.js'
import { usePortfolioStore } from '@/stores/portfolio.js'
import { formatMonthInput, formatPeriod, parseMonthInput } from '@/utils/format.js'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import TagInput from '@/components/admin/TagInput.vue'
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue'

useDocumentTitle('Experience')

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

const form = reactive({
  role: '',
  organization: '',
  employment_type: '',
  work_mode: '',
  location: '',
  start_date: '',
  end_date: '',
  is_current: false,
  highlights: [],
  technologies: [],
  highlightDraft: '',
  period: '',
  description: '',
  sort_order: 0,
})
const errors = reactive({
  role: '',
  organization: '',
  employment_type: '',
  work_mode: '',
  location: '',
  start_date: '',
  end_date: '',
  is_current: '',
  highlights: '',
  technologies: '',
  period: '',
  description: '',
  sort_order: '',
})
const showLegacyText = ref(false)
const now = new Date()
const currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
const legacyTextVisible = computed(() => isEditing.value && showLegacyText.value)
const datePreview = computed(() => {
  const start = parseMonthInput(form.start_date)
  if (!start) return ''

  const end = form.is_current ? null : parseMonthInput(form.end_date)
  if (!form.is_current && form.end_date && (!end || end < start)) return ''

  return formatPeriod(`${start}-01`, end ? `${end}-01` : null, form.is_current)
})

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
  Object.assign(form, {
    role: '',
    organization: '',
    employment_type: '',
    work_mode: '',
    location: '',
    start_date: '',
    end_date: '',
    is_current: false,
    highlights: [],
    technologies: [],
    highlightDraft: '',
    period: '',
    description: '',
    sort_order: experiences.value.length + 1,
  })
  showLegacyText.value = false
  Object.keys(errors).forEach((k) => (errors[k] = ''))
  modalOpen.value = true
}

function openEdit(exp) {
  isEditing.value = true
  editingId.value = exp.id
  Object.assign(form, {
    role: exp.role || '',
    organization: exp.organization || '',
    employment_type: exp.employment_type || '',
    work_mode: exp.work_mode || '',
    location: exp.location || '',
    start_date: formatMonthInput(exp.start_date),
    end_date: formatMonthInput(exp.end_date),
    is_current: Boolean(exp.is_current),
    highlights: Array.isArray(exp.highlights) ? [...exp.highlights] : [],
    technologies: Array.isArray(exp.technologies) ? [...exp.technologies] : [],
    highlightDraft: '',
    period: exp.period || '',
    description: exp.description || '',
    sort_order: exp.sort_order,
  })
  showLegacyText.value = Boolean(exp.period || exp.description)
  Object.keys(errors).forEach((k) => (errors[k] = ''))
  modalOpen.value = true
}

function addHighlight() {
  if (form.highlights.length >= 6) return
  form.highlights.push('')
}

function removeHighlight(index) {
  form.highlights.splice(index, 1)
}

function moveHighlight(index, direction) {
  const targetIndex = index + direction
  if (targetIndex < 0 || targetIndex >= form.highlights.length) return
  const [highlight] = form.highlights.splice(index, 1)
  form.highlights.splice(targetIndex, 0, highlight)
}

function toApiMonth(month) {
  const parsedMonth = parseMonthInput(month)
  return parsedMonth ? `${parsedMonth}-01` : null
}

function normalizeMonthField(field) {
  if (!form[field].trim()) {
    errors[field] = ''
    return
  }

  const parsedMonth = parseMonthInput(form[field])
  if (!parsedMonth) {
    errors[field] = 'Enter a month and year like Apr 2024 or 04/2024.'
    return
  }

  form[field] = formatMonthInput(parsedMonth)
  errors[field] = ''
}

function validateDateFields() {
  let valid = true
  const dateError = 'Enter a month and year like Apr 2024 or 04/2024.'

  for (const field of ['start_date', 'end_date']) {
    if (form[field].trim() && !parseMonthInput(form[field])) {
      errors[field] = dateError
      valid = false
    }
  }

  const start = parseMonthInput(form.start_date)
  const end = form.is_current ? null : parseMonthInput(form.end_date)

  if (start && start > currentMonth) {
    errors.start_date = 'Start date cannot be in the future.'
    valid = false
  }

  if (start && end && end < start) {
    errors.end_date = 'End date must be on or after the start date.'
    valid = false
  }

  return valid
}

function displayedPeriod(exp) {
  return formatPeriod(exp.start_date, exp.end_date, exp.is_current) || exp.period || ''
}

function openDelete(exp) {
  deleteTarget.value = exp
  confirmOpen.value = true
}

async function handleSave() {
  Object.keys(errors).forEach((k) => (errors[k] = ''))
  if (!validateDateFields()) return
  isSaving.value = true

  try {
    const highlights = form.highlights.map((highlight) => highlight.trim()).filter(Boolean)
    const technologies = form.technologies.map((technology) => technology.trim()).filter(Boolean)
    const payload = {
      role: form.role.trim(),
      organization: form.organization.trim(),
      employment_type: form.employment_type || null,
      work_mode: form.work_mode || null,
      location: form.location.trim() || null,
      start_date: toApiMonth(form.start_date),
      end_date: form.is_current ? null : toApiMonth(form.end_date),
      is_current: form.is_current,
      highlights: highlights.length ? highlights : null,
      technologies: technologies.length ? technologies : null,
      period: form.period.trim() || null,
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
    await Promise.all([load(), portfolioStore.refresh()])
  } catch (err) {
    if (err.fieldErrors) {
      Object.entries(err.fieldErrors).forEach(([field, messages]) => {
        const errorField = field.startsWith('highlights.') ? 'highlights'
          : field.startsWith('technologies.') ? 'technologies'
            : field
        if (errorField in errors) errors[errorField] = messages[0]
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
    await portfolioStore.refresh()
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
            <div class="min-w-0">
              <p class="font-semibold text-fg">{{ exp.role }}</p>
              <p class="text-sm text-accent font-medium">{{ exp.organization }}</p>
              <span
                v-if="exp.is_current"
                class="mt-1 inline-flex rounded-full bg-accent-soft px-2 py-0.5 text-xs font-medium text-accent"
              >
                Current
              </span>
            </div>
            <span class="max-w-full shrink-0 rounded-full border border-line bg-surface-2 px-2.5 py-1 text-xs font-mono text-muted">
              {{ displayedPeriod(exp) }}
            </span>
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
    <BaseModal v-model:open="modalOpen" :title="isEditing ? 'Edit Experience' : 'Add Experience'" max-width="max-w-2xl">
      <div class="space-y-5">
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

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div class="space-y-1.5">
            <label for="exp-employment-type" class="block text-sm font-medium text-fg">Employment type</label>
            <select id="exp-employment-type" v-model="form.employment_type" class="input-field w-full" :class="errors.employment_type ? 'border-danger' : ''">
              <option value="">Select type</option>
              <option>Full-time</option>
              <option>Part-time</option>
              <option>Internship</option>
              <option>Freelance</option>
              <option>Contract</option>
              <option>Volunteer</option>
            </select>
            <p v-if="errors.employment_type" class="text-xs text-danger">{{ errors.employment_type }}</p>
          </div>

          <div class="space-y-1.5">
            <label for="exp-work-mode" class="block text-sm font-medium text-fg">Work mode</label>
            <select id="exp-work-mode" v-model="form.work_mode" class="input-field w-full" :class="errors.work_mode ? 'border-danger' : ''">
              <option value="">Select work mode</option>
              <option>Remote</option>
              <option>On-site</option>
              <option>Hybrid</option>
            </select>
            <p v-if="errors.work_mode" class="text-xs text-danger">{{ errors.work_mode }}</p>
          </div>
        </div>

        <div class="space-y-1.5">
          <label for="exp-location" class="block text-sm font-medium text-fg">Location</label>
          <input id="exp-location" v-model="form.location" type="text" maxlength="100" placeholder="City, Country" class="input-field w-full" :class="errors.location ? 'border-danger' : ''" />
          <p v-if="errors.location" class="text-xs text-danger">{{ errors.location }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div class="space-y-1.5">
            <label for="exp-start-date" class="block text-sm font-medium text-fg">Start date</label>
            <input
              id="exp-start-date"
              v-model="form.start_date"
              type="text"
              inputmode="text"
              autocomplete="off"
              maxlength="20"
              placeholder="Apr 2024 or 04/2024"
              aria-describedby="exp-date-help"
              :aria-invalid="Boolean(errors.start_date)"
              class="input-field w-full"
              :class="errors.start_date ? 'border-danger' : ''"
              @input="errors.start_date = ''"
              @blur="normalizeMonthField('start_date')"
            />
            <p v-if="errors.start_date" class="text-xs text-danger">{{ errors.start_date }}</p>
          </div>
          <div class="space-y-1.5">
            <label for="exp-end-date" class="block text-sm font-medium text-fg">End date</label>
            <input
              id="exp-end-date"
              v-model="form.end_date"
              type="text"
              inputmode="text"
              autocomplete="off"
              maxlength="20"
              placeholder="Aug 2026 or 08/2026"
              aria-describedby="exp-date-help"
              :aria-invalid="Boolean(errors.end_date)"
              :disabled="form.is_current"
              class="input-field w-full disabled:cursor-not-allowed disabled:opacity-60"
              :class="errors.end_date ? 'border-danger' : ''"
              @input="errors.end_date = ''"
              @blur="normalizeMonthField('end_date')"
            />
            <p v-if="errors.end_date" class="text-xs text-danger">{{ errors.end_date }}</p>
          </div>
        </div>
        <p id="exp-date-help" class="-mt-3 text-xs text-muted">
          Type a month and year, such as Apr 2024 or 04/2024. The duration is calculated automatically.
        </p>
        <p v-if="datePreview" class="-mt-3 text-sm font-mono text-accent" aria-live="polite">
          Calculated period: {{ datePreview }}
        </p>

        <div class="space-y-1">
          <label class="inline-flex min-h-11 items-center gap-2 text-sm text-fg">
            <input v-model="form.is_current" type="checkbox" class="h-4 w-4 accent-accent" @change="form.is_current && (form.end_date = '')" />
            I currently work here
          </label>
          <p v-if="errors.is_current" class="text-xs text-danger">{{ errors.is_current }}</p>
        </div>

        <!-- Highlights -->
        <div class="space-y-2">
          <div class="flex items-center justify-between gap-3">
            <label class="block text-sm font-medium text-fg">Highlights</label>
            <span class="text-xs text-muted">{{ form.highlights.length }}/6</span>
          </div>
          <div v-for="(highlight, index) in form.highlights" :key="index" class="space-y-1.5 rounded-control border border-line bg-surface-2 p-3">
            <textarea
              v-model="form.highlights[index]"
              :aria-label="`Highlight ${index + 1}`"
              rows="2"
              maxlength="200"
              class="input-field w-full resize-y"
              :class="errors.highlights ? 'border-danger' : ''"
            />
            <div class="flex flex-wrap items-center justify-between gap-2">
              <span class="text-xs text-muted">{{ highlight.length }}/200</span>
              <div class="flex items-center gap-1">
                <button type="button" class="min-h-11 min-w-11 rounded-control text-muted hover:text-accent focus-ring disabled:cursor-not-allowed disabled:opacity-50" :aria-label="`Move highlight ${index + 1} up`" :disabled="index === 0" @click="moveHighlight(index, -1)">
                  <svg class="mx-auto h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18 15-6-6-6 6" /></svg>
                </button>
                <button type="button" class="min-h-11 min-w-11 rounded-control text-muted hover:text-accent focus-ring disabled:cursor-not-allowed disabled:opacity-50" :aria-label="`Move highlight ${index + 1} down`" :disabled="index === form.highlights.length - 1" @click="moveHighlight(index, 1)">
                  <svg class="mx-auto h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6" /></svg>
                </button>
                <button type="button" class="min-h-11 min-w-11 rounded-control text-danger hover:bg-danger/10 focus-ring" :aria-label="`Remove highlight ${index + 1}`" @click="removeHighlight(index)">
                  <svg class="mx-auto h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14M10 10v6m4-6v6" /></svg>
                </button>
              </div>
            </div>
          </div>
          <p v-if="errors.highlights" class="text-xs text-danger">{{ errors.highlights }}</p>
          <button v-if="form.highlights.length < 6" type="button" class="min-h-11 rounded-control px-3 text-sm font-medium text-accent hover:bg-accent-soft focus-ring" @click="addHighlight">
            Add highlight
          </button>
          <p class="text-xs text-muted">Start with a verb and add a result when you can, for example: Cut page load time by 30%</p>
        </div>

        <!-- Technologies -->
        <div class="space-y-1.5">
          <TagInput v-model="form.technologies" label="Technologies" placeholder="Add a technology..." :max-tags="8" />
          <p v-if="errors.technologies" class="text-xs text-danger">{{ errors.technologies }}</p>
          <p class="text-xs text-muted">{{ form.technologies.length }}/8 technologies</p>
        </div>

        <details v-if="legacyTextVisible" class="rounded-card border border-line">
          <summary class="flex min-h-11 cursor-pointer items-center px-4 text-sm font-medium text-fg focus-ring">
            Legacy text
          </summary>
          <div class="space-y-4 border-t border-line p-4">
            <p class="text-xs text-muted">Used only when dates are empty</p>
            <div class="space-y-1.5">
              <label for="exp-period" class="block text-sm font-medium text-fg">Period</label>
              <input id="exp-period" v-model="form.period" type="text" maxlength="50" class="input-field w-full" :class="errors.period ? 'border-danger' : ''" />
              <p v-if="errors.period" class="text-xs text-danger">{{ errors.period }}</p>
            </div>
            <div class="space-y-1.5">
              <label for="exp-desc" class="block text-sm font-medium text-fg">Description</label>
              <textarea id="exp-desc" v-model="form.description" rows="4" maxlength="1500" class="input-field w-full resize-y" :class="errors.description ? 'border-danger' : ''" />
              <div class="flex justify-between gap-3">
                <p v-if="errors.description" class="text-xs text-danger">{{ errors.description }}</p>
                <p class="ml-auto text-xs text-muted">{{ form.description.length }}/1500</p>
              </div>
            </div>
          </div>
        </details>

        <!-- Sort order -->
        <div class="space-y-1.5">
          <label for="exp-order" class="block text-sm font-medium text-fg">Sort Order</label>
          <input id="exp-order" v-model="form.sort_order" type="number" min="0" class="input-field w-24" :class="errors.sort_order ? 'border-danger' : ''" />
          <p v-if="errors.sort_order" class="text-xs text-danger">{{ errors.sort_order }}</p>
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

select.input-field {
  @apply min-h-11;
}
</style>
