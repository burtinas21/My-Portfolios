<!--
  CvView.vue — Admin CV/Resume Management
  ───────────────────────────────────────────
  Upload, replace, hide/show, delete, and preview the portfolio CV (PDF only).
  - Validates PDF type and size (5MB) before upload
  - Shows upload progress
  - Displays current file info: name, size, uploads, downloads, visibility
  - Drag-and-drop support with keyboard accessibility
-->

<script setup>
import { ref, onMounted, computed } from 'vue'
import { getCv, uploadCv, toggleCvPublic, deleteCv, getCvPreviewUrl } from '@/api/admin/cv.js'
import { useToast } from '@/composables/useToast.js'
import { useDocumentTitle } from '@/composables/useDocumentTitle.js'
import { usePortfolioStore } from '@/stores/portfolio.js'
import BaseButton from '@/components/ui/BaseButton.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue'

useDocumentTitle('CV')

const toast = useToast()
const portfolioStore = usePortfolioStore()

const cvData = ref(null) // { exists, original_name, size, is_public, download_count, updated_at }
const isLoading = ref(true)
const loadError = ref('')

const isUploading = ref(false)
const uploadProgress = ref(0)
const uploadError = ref('')

const isToggling = ref(false)
const isDeleting = ref(false)
const showDeleteConfirm = ref(false)

const isDragging = ref(false)

onMounted(async () => {
  await loadCv()
})

async function loadCv() {
  isLoading.value = true
  loadError.value = ''
  try {
    cvData.value = await getCv()
  } catch (err) {
    loadError.value = err.message || 'Failed to load CV info.'
  } finally {
    isLoading.value = false
  }
}

const cvExists = computed(() => cvData.value?.exists === true)
const formattedSize = computed(() => {
  if (!cvData.value?.size) return ''
  const kb = cvData.value.size / 1024
  return kb >= 1024 ? `${(kb / 1024).toFixed(1)} MB` : `${Math.round(kb)} KB`
})
const formattedDate = computed(() => {
  if (!cvData.value?.updated_at) return ''
  try {
    return new Intl.DateTimeFormat('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    }).format(new Date(cvData.value.updated_at))
  } catch {
    return ''
  }
})

function handleFileSelect(event) {
  const file = event.target.files?.[0]
  if (file) {
    handleFile(file)
  }
  // Reset input so the same file can be selected again
  event.target.value = ''
}

function handleDrop(event) {
  event.preventDefault()
  isDragging.value = false

  const file = event.dataTransfer?.files?.[0]
  if (file) {
    handleFile(file)
  }
}

function handleDragOver(event) {
  event.preventDefault()
  isDragging.value = true
}

function handleDragLeave() {
  isDragging.value = false
}

async function handleFile(file) {
  // Client-side validation
  if (file.type !== 'application/pdf') {
    toast.error('Only PDF files are allowed.')
    return
  }
  if (file.size > 5 * 1024 * 1024) {
    toast.error('PDF must be smaller than 5MB.')
    return
  }

  isUploading.value = true
  uploadProgress.value = 0
  uploadError.value = ''

  try {
    const result = await uploadCv(file, (percentage) => {
      uploadProgress.value = percentage
    })
    cvData.value = result
    toast.success(cvExists.value ? 'CV replaced successfully.' : 'CV uploaded successfully.')
    await portfolioStore.refresh()
    uploadProgress.value = 100
  } catch (err) {
    uploadError.value = err.message || 'Upload failed.'
    if (err.fieldErrors?.file) {
      uploadError.value = err.fieldErrors.file[0]
    }
    toast.error(uploadError.value)
  } finally {
    isUploading.value = false
    setTimeout(() => {
      uploadProgress.value = 0
      uploadError.value = ''
    }, 2000)
  }
}

async function handleTogglePublic() {
  if (!cvExists.value) return

  isToggling.value = true
  try {
    const result = await toggleCvPublic(!cvData.value.is_public)
    cvData.value = result
    await portfolioStore.refresh()
    toast.success(
      cvData.value.is_public
        ? 'CV is now visible on your site.'
        : 'CV is now hidden from your site.'
    )
  } catch (err) {
    toast.error(err.message || 'Failed to update visibility.')
  } finally {
    isToggling.value = false
  }
}

async function handlePreview() {
  if (!cvExists.value) return

  try {
    const blobUrl = await getCvPreviewUrl()
    window.open(blobUrl, '_blank', 'noopener,noreferrer')
  } catch (err) {
    toast.error('Failed to load preview.')
  }
}

function confirmDelete() {
  showDeleteConfirm.value = true
}

async function handleDelete() {
  isDeleting.value = true
  try {
    await deleteCv()
    cvData.value = { exists: false }
    showDeleteConfirm.value = false
    toast.success('CV deleted successfully.')
    await portfolioStore.refresh()
  } catch (err) {
    toast.error(err.message || 'Failed to delete CV.')
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <div class="max-w-3xl space-y-6">
    <!-- Header -->
    <div>
      <h2 class="text-2xl font-bold text-fg">CV / Resume</h2>
      <p class="text-sm text-muted mt-1">
        Upload your CV as a PDF. Visitors can download it from the Experience section.
      </p>
    </div>

    <!-- Error state -->
    <div
      v-if="loadError"
      class="p-4 rounded-card bg-danger/10 border border-danger/30 text-danger text-sm"
    >
      {{ loadError }}
    </div>

    <!-- Loading skeleton -->
    <div v-else-if="isLoading" class="space-y-5">
      <div class="h-48 skeleton rounded-card" />
    </div>

    <template v-else>
      <!-- Current file card (when CV exists) -->
      <div
        v-if="cvExists"
        class="bg-surface border border-line rounded-card p-6 shadow-card space-y-5"
      >
        <div class="flex items-start gap-4">
          <!-- File icon -->
          <div
            class="shrink-0 w-14 h-14 rounded-card bg-accent-soft text-accent flex items-center justify-center"
          >
            <svg
              class="w-7 h-7"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
              <polyline points="14 2 14 8 20 8" />
              <line x1="16" y1="13" x2="8" y2="13" />
              <line x1="16" y1="17" x2="8" y2="17" />
              <polyline points="10 9 9 9 8 9" />
            </svg>
          </div>

          <!-- File info -->
          <div class="flex-1 min-w-0">
            <h3 class="text-base font-semibold text-fg truncate">
              {{ cvData.original_name }}.pdf
            </h3>
            <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted">
              <span>{{ formattedSize }}</span>
              <span>•</span>
              <span>Uploaded {{ formattedDate }}</span>
              <span>•</span>
              <span>{{ cvData.download_count }} downloads</span>
            </div>
            <div class="mt-3">
              <span
                :class="[
                  'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
                  cvData.is_public
                    ? 'bg-success/10 text-success'
                    : 'bg-muted/10 text-muted',
                ]"
              >
                <svg
                  class="w-3.5 h-3.5"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2.5"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <path v-if="cvData.is_public" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                  <circle v-if="cvData.is_public" cx="12" cy="12" r="3" />
                  <path
                    v-else
                    d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"
                  />
                  <line v-if="!cvData.is_public" x1="1" y1="1" x2="23" y2="23" />
                </svg>
                {{ cvData.is_public ? 'Visible on your site' : 'Hidden from your site' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Toggle visibility -->
        <div class="flex items-center justify-between pt-3 border-t border-line">
          <div class="flex-1 pr-4">
            <label for="cv-public-toggle" class="block text-sm font-medium text-fg">
              Show on public site
            </label>
            <p class="text-xs text-muted mt-0.5">
              When enabled, visitors can download your CV from the Experience section.
            </p>
          </div>
          <button
            id="cv-public-toggle"
            type="button"
            role="switch"
            :aria-checked="cvData.is_public"
            :disabled="isToggling"
            :class="[
              'relative inline-flex h-6 w-11 shrink-0 rounded-full transition-colors focus-ring',
              cvData.is_public ? 'bg-accent' : 'bg-line',
              isToggling ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
            ]"
            @click="handleTogglePublic"
          >
            <span
              :class="[
                'inline-block h-5 w-5 rounded-full bg-white shadow-sm transition-transform',
                cvData.is_public ? 'translate-x-5.5' : 'translate-x-0.5',
              ]"
              style="margin-top: 2px"
            />
          </button>
        </div>

        <!-- Actions -->
        <div class="flex flex-wrap gap-3 pt-3 border-t border-line">
          <BaseButton variant="secondary" size="sm" @click="handlePreview">
            <svg
              class="w-4 h-4"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
              <circle cx="12" cy="12" r="3" />
            </svg>
            Preview
          </BaseButton>

          <label class="cursor-pointer">
            <input
              type="file"
              accept="application/pdf"
              class="hidden"
              :disabled="isUploading"
              @change="handleFileSelect"
            />
            <span
              :class="[
                'inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-medium rounded-control',
                'border border-line bg-transparent text-fg hover-button-outlined focus-ring',
                isUploading ? 'opacity-50 cursor-not-allowed' : '',
              ]"
            >
              <svg
                class="w-4 h-4"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" />
                <polyline points="17 8 12 3 7 8" />
                <line x1="12" y1="3" x2="12" y2="15" />
              </svg>
              Replace
            </span>
          </label>

          <BaseButton variant="danger" size="sm" @click="confirmDelete">
            <svg
              class="w-4 h-4"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <polyline points="3 6 5 6 21 6" />
              <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" />
            </svg>
            Delete
          </BaseButton>
        </div>
      </div>

      <!-- Upload area (when no CV exists or uploading replacement) -->
      <div
        v-if="!cvExists"
        class="bg-surface border border-line rounded-card p-8 shadow-card space-y-6"
      >
        <!-- Empty state hint -->
        <div class="text-center space-y-2">
          <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-accent-soft text-accent">
            <svg
              class="w-8 h-8"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
              <polyline points="14 2 14 8 20 8" />
            </svg>
          </div>
          <h3 class="text-base font-semibold text-fg">Upload Your CV</h3>
          <p class="text-sm text-muted max-w-md mx-auto">
            Upload a PDF version of your resume. Visitors will be able to download it from the Experience section of your site.
          </p>
          <p class="text-xs text-muted/80 max-w-sm mx-auto pt-2">
            <strong>Privacy tip:</strong> Remove your home address and phone number before uploading. This file will be public.
          </p>
        </div>

        <!-- Drag and drop zone -->
        <div
          :class="[
            'relative rounded-card border-2 border-dashed transition-colors',
            isDragging
              ? 'border-accent bg-accent-soft'
              : 'border-line bg-surface-2 hover:border-accent/50',
          ]"
          @drop="handleDrop"
          @dragover="handleDragOver"
          @dragleave="handleDragLeave"
        >
          <label
            class="block cursor-pointer p-8 text-center"
            :class="isUploading ? 'pointer-events-none' : ''"
          >
            <input
              type="file"
              accept="application/pdf"
              class="sr-only"
              :disabled="isUploading"
              @change="handleFileSelect"
            />
            <svg
              class="w-10 h-10 mx-auto text-muted mb-3"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.5"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" />
              <polyline points="17 8 12 3 7 8" />
              <line x1="12" y1="3" x2="12" y2="15" />
            </svg>
            <p class="text-sm font-medium text-fg">
              <span class="text-accent">Choose a PDF file</span> or drag it here
            </p>
            <p class="text-xs text-muted mt-1">Maximum file size: 5MB</p>
          </label>
        </div>
      </div>

      <!-- Upload progress -->
      <div
        v-if="isUploading"
        class="bg-surface border border-line rounded-card p-5 shadow-card space-y-3"
      >
        <div class="flex items-center justify-between text-sm">
          <span class="font-medium text-fg">Uploading...</span>
          <span class="text-muted">{{ uploadProgress }}%</span>
        </div>
        <div class="w-full h-2 rounded-full bg-surface-2 overflow-hidden">
          <div
            class="h-full w-full origin-left bg-accent transition-transform duration-300 ease-out"
            :style="{ transform: `scaleX(${uploadProgress / 100})` }"
          />
        </div>
      </div>

      <!-- Upload error -->
      <div
        v-if="uploadError"
        class="p-4 rounded-card bg-danger/10 border border-danger/30 text-danger text-sm"
      >
        {{ uploadError }}
      </div>
    </template>

    <!-- Delete confirmation dialog -->
    <ConfirmDialog
      v-model:open="showDeleteConfirm"
      title="Delete CV"
      message="This will permanently delete your CV. Visitors will no longer be able to download it."
      confirm-label="Delete"
      :loading="isDeleting"
      @confirm="handleDelete"
    />
  </div>
</template>

<style scoped>
/* Visually hidden but accessible to screen readers */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}
</style>
