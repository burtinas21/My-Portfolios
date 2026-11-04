<!--
  ImageUpload.vue — Drag-and-drop / click-to-upload image widget
  ───────────────────────────────────────────────────────────────
  Handles image upload to POST /admin/uploads.

  Props:
  - modelValue: string|null — current image path, stored as a relative "/storage/..." value (v-model)
  - label: label text (default "Image")

  Events:
  - update:modelValue: emits the relative "path" string ("/storage/uploads/...") after upload

  Features:
  - Drag-and-drop or click to choose file
  - Shows upload progress bar
  - Previews the current/uploaded image via assetUrl() helper
  - Clear button to remove the current image path

  Used by: admin ProjectsView, ProfileView
-->

<script setup>
import { ref } from 'vue'
import { uploadImage } from '@/api/admin/uploads.js'
import { useToast } from '@/composables/useToast.js'
import { assetUrl } from '@/utils/url.js'

const props = defineProps({
  modelValue: { type: String, default: null },
  label: { type: String, default: 'Image' },
})

const emit = defineEmits(['update:modelValue'])
const toast = useToast()

const fileInputRef = ref(null)
const isDragging = ref(false)
const isUploading = ref(false)
const progress = ref(0)

function openFilePicker() {
  fileInputRef.value?.click()
}

function onDragover(e) {
  e.preventDefault()
  isDragging.value = true
}

function onDragleave() {
  isDragging.value = false
}

function onDrop(e) {
  e.preventDefault()
  isDragging.value = false
  const file = e.dataTransfer?.files?.[0]
  if (file) processFile(file)
}

function onFileChange(e) {
  const file = e.target.files?.[0]
  if (file) processFile(file)
  // Reset so the same file can be re-selected without triggering a stale event
  e.target.value = ''
}

async function processFile(file) {
  // Client-side type guard (server validates too, this avoids the round-trip)
  const allowed = ['image/jpeg', 'image/png', 'image/webp']
  if (!allowed.includes(file.type)) {
    toast.error('Only JPG, PNG, or WebP images are allowed.')
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    toast.error('Image must be smaller than 2MB.')
    return
  }

  isUploading.value = true
  progress.value = 0

  try {
    const data = await uploadImage(file, (pct) => {
      progress.value = pct
    })
    // Emit the relative "path" (e.g. "/storage/uploads/file.png") — never the full URL.
    // assetUrl() will add VITE_BACKEND_URL when the image needs to be displayed.
    emit('update:modelValue', data.path)
    toast.success('Image uploaded successfully.')
  } catch (err) {
    toast.error(err.message || 'Upload failed.')
  } finally {
    isUploading.value = false
    progress.value = 0
  }
}

function clearImage() {
  emit('update:modelValue', null)
}
</script>

<template>
  <div class="space-y-2">
    <label class="block text-sm font-medium text-fg">{{ label }}</label>

    <!-- Drop zone / preview -->
    <div
      class="relative rounded-card border-2 border-dashed transition-colors overflow-hidden"
      :class="[
        isDragging ? 'border-accent bg-accent-soft/30' : 'border-line bg-surface-2',
        isUploading ? 'pointer-events-none' : 'cursor-pointer',
      ]"
      @click="openFilePicker"
      @dragover="onDragover"
      @dragleave="onDragleave"
      @drop="onDrop"
    >
      <!-- Preview when image exists (assetUrl converts relative path → full URL) -->
      <div v-if="modelValue && !isUploading" class="relative group">
        <img
          :src="assetUrl(modelValue)"
          alt="Preview"
          class="w-full h-48 object-cover"
        />
        <!-- Hover overlay with Replace / Remove actions -->
        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
          <button
            type="button"
            class="px-3 py-1.5 rounded-control bg-white/20 text-white text-sm font-medium hover:bg-white/30 transition-colors"
            @click.stop="openFilePicker"
          >
            Replace
          </button>
          <button
            type="button"
            class="px-3 py-1.5 rounded-control bg-danger/80 text-white text-sm font-medium hover:bg-danger transition-colors"
            @click.stop="clearImage"
          >
            Remove
          </button>
        </div>
      </div>

      <!-- Upload progress spinner + bar -->
      <div v-else-if="isUploading" class="flex flex-col items-center justify-center gap-3 h-48 px-6">
        <svg class="w-8 h-8 animate-spin text-accent" viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/>
          <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="opacity-75"/>
        </svg>
        <div class="w-full max-w-xs">
          <div class="h-1.5 bg-line rounded-full overflow-hidden">
            <div
              class="h-full bg-accent transition-all duration-200 rounded-full"
              :style="{ width: progress + '%' }"
            />
          </div>
          <p class="text-xs text-muted text-center mt-1">Uploading... {{ progress }}%</p>
        </div>
      </div>

      <!-- Empty drop zone prompt -->
      <div v-else class="flex flex-col items-center justify-center gap-3 h-48 px-6 text-center">
        <div class="w-12 h-12 rounded-full bg-surface border border-line flex items-center justify-center text-muted">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" stroke-linecap="round" stroke-linejoin="round"/>
            <polyline points="17 8 12 3 7 8" stroke-linecap="round" stroke-linejoin="round"/>
            <line x1="12" y1="3" x2="12" y2="15" stroke-linecap="round"/>
          </svg>
        </div>
        <div>
          <p class="text-sm font-medium text-fg">Drag &amp; drop or <span class="text-accent">click to upload</span></p>
          <p class="text-xs text-muted mt-0.5">JPG, PNG, WebP • Max 2MB</p>
        </div>
      </div>
    </div>

    <!-- Hidden file input -->
    <input
      ref="fileInputRef"
      type="file"
      accept="image/jpeg,image/png,image/webp"
      class="hidden"
      @change="onFileChange"
    />

    <!-- Show the stored relative path so the admin can confirm what is saved -->
    <div v-if="modelValue" class="flex items-center gap-2">
      <span class="text-xs text-muted truncate flex-1">{{ modelValue }}</span>
      <button
        type="button"
        class="text-xs text-danger hover:underline shrink-0"
        @click="clearImage"
      >
        Clear
      </button>
    </div>
  </div>
</template>
