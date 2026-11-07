<!--
  ProjectsView.vue — Admin Projects Manager
  ──────────────────────────────────────────
  Full CRUD for portfolio projects (including unpublished drafts).
  - List all projects with publish status badges
  - Add / Edit via modal (title, summary, detail, tag, year, url, image, stack, publish flag, sort_order)
  - Delete with confirmation dialog
-->

<script setup>
import { ref, reactive, onMounted } from 'vue'
import {
  getAdminProjects,
  createAdminProject,
  updateAdminProject,
  deleteAdminProject,
} from '@/api/admin/projects.js'
import { useToast } from '@/composables/useToast.js'
import { useDocumentTitle } from '@/composables/useDocumentTitle.js'
import { usePortfolioStore } from '@/stores/portfolio.js'
import { assetUrl } from '@/utils/url.js'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue'
import ImageUpload from '@/components/admin/ImageUpload.vue'
import TagInput from '@/components/admin/TagInput.vue'

useDocumentTitle('Projects')

const toast = useToast()
const portfolioStore = usePortfolioStore()

const projects = ref([])
const isLoading = ref(true)
const loadError = ref('')

// Modal
const modalOpen = ref(false)
const isEditing = ref(false)
const isSaving = ref(false)
const editingId = ref(null)

const emptyForm = () => ({
  title: '',
  summary: '',
  detail: '',
  tag: '',
  year: new Date().getFullYear(),
  url: '',
  github_url: '',
  image: null,
  stack: [],
  is_published: false,
  sort_order: 0,
})

const form = reactive(emptyForm())

const errors = reactive({
  title: '', summary: '', detail: '', tag: '', year: '', url: '', github_url: '', image: '', stack: '', sort_order: '',
})

// Delete confirm
const confirmOpen = ref(false)
const isDeleting = ref(false)
const deleteTarget = ref(null)

// Tab in modal
const activeTab = ref('basic')

async function load() {
  isLoading.value = true
  loadError.value = ''
  try {
    projects.value = await getAdminProjects()
  } catch (err) {
    loadError.value = err.message || 'Failed to load projects.'
  } finally {
    isLoading.value = false
  }
}

onMounted(load)

function openCreate() {
  isEditing.value = false
  editingId.value = null
  Object.assign(form, emptyForm())
  form.sort_order = projects.value.length + 1
  Object.keys(errors).forEach((k) => (errors[k] = ''))
  activeTab.value = 'basic'
  modalOpen.value = true
}

function openEdit(project) {
  isEditing.value = true
  editingId.value = project.id
  Object.assign(form, {
    title: project.title || '',
    summary: project.summary || '',
    detail: project.detail || '',
    tag: project.tag || '',
    year: project.year || new Date().getFullYear(),
    url: project.url || '',
    github_url: project.github_url || '',
    image: project.image || null,
    stack: Array.isArray(project.stack) ? [...project.stack] : [],
    is_published: !!project.is_published,
    sort_order: project.sort_order ?? 0,
  })
  Object.keys(errors).forEach((k) => (errors[k] = ''))
  activeTab.value = 'basic'
  modalOpen.value = true
}

function openDelete(project) {
  deleteTarget.value = project
  confirmOpen.value = true
}

async function handleSave() {
  Object.keys(errors).forEach((k) => (errors[k] = ''))
  isSaving.value = true

  try {
    const payload = {
      title: form.title.trim(),
      summary: form.summary.trim(),
      detail: form.detail.trim() || null,
      tag: form.tag.trim(),
      year: Number(form.year),
      url: form.url.trim() || null,
      github_url: form.github_url.trim() || null,
      image: form.image || null,
      stack: form.stack,
      is_published: form.is_published,
      sort_order: Number(form.sort_order),
    }

    if (isEditing.value) {
      const updated = await updateAdminProject(editingId.value, payload)
      const idx = projects.value.findIndex((p) => p.id === editingId.value)
      if (idx !== -1) projects.value[idx] = updated
      toast.success('Project updated.')
    } else {
      const created = await createAdminProject(payload)
      projects.value.unshift(created)
      toast.success('Project created.')
    }
    modalOpen.value = false
    // Refresh the public portfolio store so the change appears on the public site
    await portfolioStore.refresh()
  } catch (err) {
    if (err.fieldErrors) {
      Object.entries(err.fieldErrors).forEach(([f, msgs]) => {
        if (f in errors) errors[f] = msgs[0]
      })
      // Switch to the tab that has errors
      const basicFields = ['title', 'summary', 'tag', 'year', 'url', 'github_url', 'sort_order']
      const hasBasicError = basicFields.some((f) => errors[f])
      if (hasBasicError) activeTab.value = 'basic'
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
    await deleteAdminProject(deleteTarget.value.id)
    projects.value = projects.value.filter((p) => p.id !== deleteTarget.value.id)
    toast.success('Project deleted.')
    confirmOpen.value = false
    // Refresh the public portfolio store so deleted project disappears
    await portfolioStore.refresh()
  } catch (err) {
    toast.error(err.message || 'Delete failed.')
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <div class="max-w-5xl space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-fg">Projects</h2>
        <p class="text-sm text-muted mt-1">{{ projects.length }} project{{ projects.length !== 1 ? 's' : '' }} total.</p>
      </div>
      <BaseButton variant="primary" size="md" @click="openCreate">
        <svg class="w-4 h-4 mr-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Project
      </BaseButton>
    </div>

    <!-- Error -->
    <div v-if="loadError" class="p-4 rounded-card bg-danger/10 border border-danger/30 text-danger text-sm">{{ loadError }}</div>

    <!-- Loading -->
    <div v-else-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div v-for="i in 4" :key="i" class="h-40 skeleton rounded-card" />
    </div>

    <!-- Empty -->
    <div v-else-if="projects.length === 0" class="text-center py-16 text-muted">
      <p class="text-lg font-medium">No projects yet</p>
      <p class="text-sm mt-1">Create your first project above.</p>
    </div>

    <!-- Project grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
      <div
        v-for="project in projects"
        :key="project.id"
        class="bg-surface border border-line rounded-card shadow-card overflow-hidden flex flex-col"
      >
        <!-- Thumbnail -->
        <div class="h-36 bg-surface-2 relative overflow-hidden">
          <img
            v-if="project.image"
            :src="assetUrl(project.image)"
            :alt="project.title"
            class="w-full h-full object-cover"
          />
          <div v-else class="w-full h-full flex items-center justify-center text-muted">
            <svg class="w-8 h-8 opacity-30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <rect x="3" y="3" width="18" height="18" rx="2"/><path d="m21 15-5-5L5 21"/><circle cx="8.5" cy="8.5" r="1.5"/>
            </svg>
          </div>
          <!-- Publish badge -->
          <span
            class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-xs font-semibold"
            :class="project.is_published ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-surface-2 text-muted border border-line'"
          >
            {{ project.is_published ? 'Published' : 'Draft' }}
          </span>
        </div>

        <!-- Content -->
        <div class="p-4 flex-1 flex flex-col gap-2">
          <div class="flex items-start justify-between gap-2">
            <h3 class="text-sm font-semibold text-fg leading-tight line-clamp-2">{{ project.title }}</h3>
            <span class="text-xs text-muted shrink-0">{{ project.year }}</span>
          </div>
          <p class="text-xs text-muted line-clamp-2 flex-1">{{ project.summary }}</p>

          <div v-if="project.stack?.length" class="flex flex-wrap gap-1 mt-auto">
            <span
              v-for="tech in project.stack.slice(0, 4)"
              :key="tech"
              class="px-1.5 py-0.5 rounded bg-accent-soft text-accent text-xs"
            >
              {{ tech }}
            </span>
            <span v-if="project.stack.length > 4" class="text-xs text-muted">+{{ project.stack.length - 4 }}</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="border-t border-line px-4 py-2.5 flex items-center gap-2">
          <BaseButton variant="ghost" size="sm" class="flex-1 text-xs" @click="openEdit(project)">Edit</BaseButton>
          <BaseButton variant="ghost" size="sm" class="text-danger hover:text-danger text-xs" @click="openDelete(project)">Delete</BaseButton>
        </div>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <BaseModal
      v-model:open="modalOpen"
      :title="isEditing ? 'Edit Project' : 'New Project'"
      max-width="max-w-2xl"
    >
      <!-- Tabs -->
      <div>
        <div class="flex gap-1 mb-6 border-b border-line">
          <button
            v-for="tab in ['basic', 'media', 'settings']"
            :key="tab"
            type="button"
            class="px-4 py-2 text-sm font-medium capitalize transition-colors border-b-2 -mb-px"
            :class="activeTab === tab
              ? 'border-accent text-accent'
              : 'border-transparent text-muted hover:text-fg'"
            @click="activeTab = tab"
          >
            {{ tab === 'basic' ? 'Basic Info' : tab === 'media' ? 'Media & Stack' : 'Settings' }}
          </button>
        </div>

        <!-- Tab: Basic Info -->
        <div v-show="activeTab === 'basic'" class="space-y-4">
          <!-- Title -->
          <div class="space-y-1.5">
            <label for="proj-title" class="block text-sm font-medium text-fg">Title <span class="text-danger">*</span></label>
            <input id="proj-title" v-model="form.title" type="text" maxlength="150" placeholder="E-Commerce Platform" class="input-field w-full" :class="errors.title ? 'border-danger' : ''" />
            <p v-if="errors.title" class="text-xs text-danger">{{ errors.title }}</p>
          </div>

          <!-- Summary -->
          <div class="space-y-1.5">
            <label for="proj-summary" class="block text-sm font-medium text-fg">Summary <span class="text-danger">*</span></label>
            <input id="proj-summary" v-model="form.summary" type="text" maxlength="255" placeholder="A brief one-line description" class="input-field w-full" :class="errors.summary ? 'border-danger' : ''" />
            <div class="flex justify-between">
              <p v-if="errors.summary" class="text-xs text-danger">{{ errors.summary }}</p>
              <p class="text-xs text-muted ml-auto">{{ form.summary.length }}/255</p>
            </div>
          </div>

          <!-- Detail -->
          <div class="space-y-1.5">
            <label for="proj-detail" class="block text-sm font-medium text-fg">Full Description</label>
            <textarea id="proj-detail" v-model="form.detail" rows="5" maxlength="3000" placeholder="Detailed description of the project..." class="input-field w-full resize-none" />
            <p class="text-xs text-muted text-right">{{ form.detail.length }}/3000</p>
          </div>

          <!-- Tag + Year row -->
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <label for="proj-tag" class="block text-sm font-medium text-fg">Tag <span class="text-danger">*</span></label>
              <input id="proj-tag" v-model="form.tag" type="text" maxlength="50" placeholder="Web App" class="input-field w-full" :class="errors.tag ? 'border-danger' : ''" />
              <p v-if="errors.tag" class="text-xs text-danger">{{ errors.tag }}</p>
            </div>
            <div class="space-y-1.5">
              <label for="proj-year" class="block text-sm font-medium text-fg">Year <span class="text-danger">*</span></label>
              <input id="proj-year" v-model="form.year" type="number" min="2000" max="2100" class="input-field w-full" :class="errors.year ? 'border-danger' : ''" />
              <p v-if="errors.year" class="text-xs text-danger">{{ errors.year }}</p>
            </div>
          </div>

          <!-- Project URLs -->
          <div class="space-y-1.5">
            <label for="proj-url" class="block text-sm font-medium text-fg">Live Demo URL</label>
            <input id="proj-url" v-model="form.url" type="url" maxlength="255" placeholder="https://example.com" class="input-field w-full" :class="errors.url ? 'border-danger' : ''" />
            <p v-if="errors.url" class="text-xs text-danger">{{ errors.url }}</p>
          </div>
          <div class="space-y-1.5">
            <label for="proj-github-url" class="block text-sm font-medium text-fg">GitHub Repository URL</label>
            <input id="proj-github-url" v-model="form.github_url" type="url" maxlength="255" placeholder="https://github.com/owner/repository" class="input-field w-full" :class="errors.github_url ? 'border-danger' : ''" />
            <p v-if="errors.github_url" class="text-xs text-danger">{{ errors.github_url }}</p>
          </div>
        </div>

        <!-- Tab: Media & Stack -->
        <div v-show="activeTab === 'media'" class="space-y-6">
          <ImageUpload v-model="form.image" label="Project Screenshot" />
          <TagInput v-model="form.stack" label="Tech Stack" placeholder="Vue.js, Laravel, PostgreSQL..." />
        </div>

        <!-- Tab: Settings -->
        <div v-show="activeTab === 'settings'" class="space-y-4">
          <!-- Published toggle -->
          <div class="flex items-center justify-between p-4 rounded-card border border-line bg-surface-2">
            <div>
              <p class="text-sm font-semibold text-fg">Published</p>
              <p class="text-xs text-muted">Only published projects appear on the public site.</p>
            </div>
            <button
              type="button"
              role="switch"
              :aria-checked="form.is_published"
              class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-bg"
              :class="form.is_published ? 'bg-accent' : 'bg-line'"
              @click="form.is_published = !form.is_published"
            >
              <span
                class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform"
                :class="form.is_published ? 'translate-x-6' : 'translate-x-1'"
              />
            </button>
          </div>

          <!-- Sort order -->
          <div class="space-y-1.5">
            <label for="proj-order" class="block text-sm font-medium text-fg">Sort Order</label>
            <input id="proj-order" v-model="form.sort_order" type="number" min="0" class="input-field w-24" />
            <p class="text-xs text-muted">Lower number = shown first.</p>
          </div>
        </div>
      </div>

      <template #footer>
        <BaseButton variant="secondary" size="sm" @click="modalOpen = false">Cancel</BaseButton>
        <BaseButton variant="primary" size="sm" :loading="isSaving" @click="handleSave">
          {{ isEditing ? 'Update Project' : 'Create Project' }}
        </BaseButton>
      </template>
    </BaseModal>

    <!-- Delete Confirm -->
    <ConfirmDialog
      v-model:open="confirmOpen"
      title="Delete Project"
      :message="`'${deleteTarget?.title}' will be permanently deleted along with its image.`"
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
