<!--
  SkillsView.vue — Admin Skills Manager
  ──────────────────────────────────────
  CRUD for technical skills with category grouping.
  - List grouped by category, each row shows a small SkillIcon
  - Add / Edit via modal with icon field, live preview, and icon suggestion
  - Delete with confirmation
-->

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { getAdminSkills, createAdminSkill, updateAdminSkill, deleteAdminSkill } from '@/api/admin/skills.js'
import { useToast } from '@/composables/useToast.js'
import { useDocumentTitle } from '@/composables/useDocumentTitle.js'
import { usePortfolioStore } from '@/stores/portfolio.js'
import { suggestIcon } from '@/utils/skillIcons.js'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue'
import SkillIcon from '@/components/ui/SkillIcon.vue'

useDocumentTitle('Skills')

const toast = useToast()
const portfolioStore = usePortfolioStore()

const skills = ref([])
const isLoading = ref(true)
const loadError = ref('')

// Group skills by category for display
const grouped = computed(() => {
  const groups = {}
  for (const skill of skills.value) {
    if (!groups[skill.category]) groups[skill.category] = []
    groups[skill.category].push(skill)
  }
  return groups
})

// Modal state
const modalOpen = ref(false)
const isEditing = ref(false)
const isSaving = ref(false)
const editingId = ref(null)

const form = reactive({ category: '', name: '', level: 80, icon: '', sort_order: 0 })
const errors = reactive({ category: '', name: '', level: '', icon: '', sort_order: '' })

// Icon suggestion — populated when name matches a known skill and icon field is empty
const iconSuggestion = ref('')

// Watch the skill name and update the suggestion whenever it changes
watch(() => form.name, (newName) => {
  // Never overwrite what the user already typed into the icon field
  if (!form.icon.trim()) {
    iconSuggestion.value = suggestIcon(newName) || ''
  }
})

// Also clear suggestion if user starts typing an icon manually
watch(() => form.icon, (val) => {
  if (val.trim()) iconSuggestion.value = ''
})

/** Apply the suggested icon name into the icon field */
function applySuggestion() {
  if (iconSuggestion.value) {
    form.icon = iconSuggestion.value
    iconSuggestion.value = ''
  }
}

// Confirm delete
const confirmOpen = ref(false)
const isDeleting = ref(false)
const deleteTarget = ref(null)

async function load() {
  isLoading.value = true
  loadError.value = ''
  try {
    skills.value = await getAdminSkills()
  } catch (err) {
    loadError.value = err.message || 'Failed to load skills.'
  } finally {
    isLoading.value = false
  }
}

onMounted(load)

function openCreate() {
  isEditing.value = false
  editingId.value = null
  form.category = ''
  form.name = ''
  form.level = 80
  form.icon = ''
  form.sort_order = skills.value.length + 1
  iconSuggestion.value = ''
  Object.keys(errors).forEach((k) => (errors[k] = ''))
  modalOpen.value = true
}

function openEdit(skill) {
  isEditing.value = true
  editingId.value = skill.id
  form.category = skill.category
  form.name = skill.name
  form.level = skill.level
  form.icon = skill.icon || ''
  form.sort_order = skill.sort_order
  iconSuggestion.value = ''
  Object.keys(errors).forEach((k) => (errors[k] = ''))
  modalOpen.value = true
}

function openDelete(skill) {
  deleteTarget.value = skill
  confirmOpen.value = true
}

async function handleSave() {
  Object.keys(errors).forEach((k) => (errors[k] = ''))
  isSaving.value = true

  try {
    const payload = {
      category: form.category.trim(),
      name: form.name.trim(),
      level: Number(form.level),
      // Send null instead of empty string so the server stores NULL
      icon: form.icon.trim() || null,
      sort_order: Number(form.sort_order),
    }

    if (isEditing.value) {
      const updated = await updateAdminSkill(editingId.value, payload)
      const idx = skills.value.findIndex((s) => s.id === editingId.value)
      if (idx !== -1) skills.value[idx] = updated
      toast.success('Skill updated.')
    } else {
      const created = await createAdminSkill(payload)
      skills.value.push(created)
      toast.success('Skill added.')
    }

    modalOpen.value = false

    // Refresh the portfolio store so the public site picks up the new icon
    await portfolioStore.refresh()
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
    await deleteAdminSkill(deleteTarget.value.id)
    skills.value = skills.value.filter((s) => s.id !== deleteTarget.value.id)
    toast.success('Skill deleted.')
    confirmOpen.value = false
    // Refresh public store so deleted skill disappears
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
        <h2 class="text-2xl font-bold text-fg">Skills</h2>
        <p class="text-sm text-muted mt-1">Manage your technical skills by category.</p>
      </div>
      <BaseButton variant="primary" size="md" @click="openCreate">
        <svg class="w-4 h-4 mr-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Skill
      </BaseButton>
    </div>

    <!-- Error -->
    <div v-if="loadError" class="p-4 rounded-card bg-danger/10 border border-danger/30 text-danger text-sm">{{ loadError }}</div>

    <!-- Loading -->
    <div v-else-if="isLoading" class="space-y-3">
      <div v-for="i in 5" :key="i" class="h-16 skeleton rounded-card" />
    </div>

    <!-- Empty -->
    <div v-else-if="skills.length === 0" class="text-center py-16 text-muted">
      <p class="text-lg font-medium">No skills yet</p>
      <p class="text-sm mt-1">Add your first skill above.</p>
    </div>

    <!-- Grouped tables -->
    <div v-else class="space-y-6">
      <div v-for="(categorySkills, category) in grouped" :key="category" class="bg-surface border border-line rounded-card shadow-card overflow-hidden">
        <div class="px-5 py-3 border-b border-line bg-surface-2">
          <h3 class="text-sm font-semibold text-fg">{{ category }}</h3>
        </div>
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-line">
              <th class="text-left px-5 py-2.5 text-xs font-semibold text-muted uppercase tracking-wider">Name</th>
              <th class="text-left px-5 py-2.5 text-xs font-semibold text-muted uppercase tracking-wider w-48">Level</th>
              <th class="px-5 py-2.5" />
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="skill in categorySkills"
              :key="skill.id"
              class="border-b border-line last:border-0 hover:bg-surface-2 transition-colors"
            >
              <!-- Name cell: small SkillIcon + skill name -->
              <td class="px-5 py-3">
                <div class="flex items-center gap-2.5">
                  <SkillIcon :icon="skill.icon || ''" :name="skill.name" :size="18" />
                  <span class="font-medium text-fg">{{ skill.name }}</span>
                </div>
              </td>
              <td class="px-5 py-3">
                <div class="flex items-center gap-3">
                  <div class="flex-1 h-1.5 bg-line rounded-full overflow-hidden">
                    <div class="h-full bg-accent rounded-full" :style="{ width: skill.level + '%' }" />
                  </div>
                  <span class="text-xs text-muted w-9 text-right shrink-0">{{ skill.level }}%</span>
                </div>
              </td>
              <td class="px-5 py-3">
                <div class="flex items-center justify-end gap-2">
                  <BaseButton variant="ghost" size="sm" @click="openEdit(skill)">Edit</BaseButton>
                  <BaseButton variant="ghost" size="sm" class="text-danger hover:text-danger" @click="openDelete(skill)">Delete</BaseButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <BaseModal v-model:open="modalOpen" :title="isEditing ? 'Edit Skill' : 'Add Skill'" max-width="max-w-md">
      <div class="space-y-4">
        <!-- Category -->
        <div class="space-y-1.5">
          <label for="skill-category" class="block text-sm font-medium text-fg">Category <span class="text-danger">*</span></label>
          <input id="skill-category" v-model="form.category" type="text" maxlength="50" placeholder="Backend" class="input-field w-full" :class="errors.category ? 'border-danger' : ''" />
          <p v-if="errors.category" class="text-xs text-danger">{{ errors.category }}</p>
        </div>

        <!-- Name -->
        <div class="space-y-1.5">
          <label for="skill-name" class="block text-sm font-medium text-fg">Skill Name <span class="text-danger">*</span></label>
          <input id="skill-name" v-model="form.name" type="text" maxlength="80" placeholder="Laravel" class="input-field w-full" :class="errors.name ? 'border-danger' : ''" />
          <p v-if="errors.name" class="text-xs text-danger">{{ errors.name }}</p>
        </div>

        <!-- Icon field with live preview -->
        <div class="space-y-1.5">
          <label for="skill-icon" class="block text-sm font-medium text-fg">Icon</label>
          <div class="flex items-center gap-3">
            <!-- Live icon preview updates as the user types -->
            <SkillIcon :icon="form.icon.trim()" :name="form.name || '?'" :size="28" />
            <input
              id="skill-icon"
              v-model="form.icon"
              type="text"
              maxlength="80"
              placeholder="logos:laravel"
              class="input-field flex-1"
              :class="errors.icon ? 'border-danger' : ''"
            />
          </div>

          <!-- Server-side validation error -->
          <p v-if="errors.icon" class="text-xs text-danger">{{ errors.icon }}</p>

          <!-- Suggestion pill — only shown when a known name is matched and field is empty -->
          <button
            v-if="iconSuggestion"
            type="button"
            class="suggestion-btn"
            @click="applySuggestion"
          >
            Use {{ iconSuggestion }}
          </button>

          <p class="text-xs text-muted">
            Find the name on
            <a
              href="https://icon-sets.iconify.design/logos/"
              target="_blank"
              rel="noopener noreferrer"
              class="underline hover:text-accent transition-colors"
            >icon-sets.iconify.design</a>
            (open the Logos set and copy the name).
          </p>
        </div>

        <!-- Level -->
        <div class="space-y-1.5">
          <label for="skill-level" class="block text-sm font-medium text-fg">Proficiency Level: <span class="font-bold text-accent">{{ form.level }}%</span></label>
          <input id="skill-level" v-model="form.level" type="range" min="0" max="100" class="w-full accent-accent" />
          <p v-if="errors.level" class="text-xs text-danger">{{ errors.level }}</p>
        </div>

        <!-- Sort order -->
        <div class="space-y-1.5">
          <label for="skill-order" class="block text-sm font-medium text-fg">Sort Order</label>
          <input id="skill-order" v-model="form.sort_order" type="number" min="0" class="input-field w-24" />
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
      title="Delete Skill"
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

/* Small pill button shown when a known icon name is suggested */
.suggestion-btn {
  @apply inline-flex items-center gap-1 text-xs font-medium
         px-2.5 py-1 rounded-full
         bg-accent-soft text-accent
         border border-accent/30
         hover:bg-accent hover:text-on-accent
         transition-colors cursor-pointer;
}
</style>
