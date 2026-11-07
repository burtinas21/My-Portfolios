<!--
  CertificationImageLightbox.vue — Certificate Image Viewer
  ──────────────────────────────────────────────────────────
  Accessible image viewer with bounded zoom controls.
-->

<script setup>
import { computed, ref, watch } from 'vue'
import { ZoomIn, ZoomOut, RotateCcw } from 'lucide-vue-next'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import { assetUrl } from '@/utils/url.js'

const props = defineProps({
  open: { type: Boolean, default: false },
  image: { type: String, default: null },
  title: { type: String, default: 'Certificate' },
})

const emit = defineEmits(['update:open'])
const zoom = ref(1)
const imageUrl = computed(() => assetUrl(props.image))

watch(() => props.open, (isOpen) => {
  if (isOpen) zoom.value = 1
})

function close() {
  emit('update:open', false)
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="`Certificate: ${title}`"
    max-width="max-w-4xl"
    @update:open="emit('update:open', $event)"
  >
    <div class="space-y-4">
      <div class="flex items-center justify-center gap-2" aria-label="Image zoom controls">
        <button
          type="button"
          class="min-h-11 min-w-11 rounded-control border border-line text-muted hover-icon focus-ring disabled:opacity-40"
          :disabled="zoom <= 1"
          aria-label="Zoom out"
          title="Zoom out"
          @click="zoom = Math.max(1, zoom - 0.5)"
        >
          <ZoomOut class="mx-auto h-4 w-4" aria-hidden="true" />
        </button>
        <span class="min-w-14 text-center font-mono text-xs text-muted" aria-live="polite">{{ Math.round(zoom * 100) }}%</span>
        <button
          type="button"
          class="min-h-11 min-w-11 rounded-control border border-line text-muted hover-icon focus-ring disabled:opacity-40"
          :disabled="zoom >= 3"
          aria-label="Zoom in"
          title="Zoom in"
          @click="zoom = Math.min(3, zoom + 0.5)"
        >
          <ZoomIn class="mx-auto h-4 w-4" aria-hidden="true" />
        </button>
        <button
          type="button"
          class="min-h-11 min-w-11 rounded-control border border-line text-muted hover-icon focus-ring"
          aria-label="Reset zoom"
          title="Reset zoom"
          @click="zoom = 1"
        >
          <RotateCcw class="mx-auto h-4 w-4" aria-hidden="true" />
        </button>
      </div>

      <div class="flex max-h-[60vh] items-center justify-center overflow-auto rounded-control border border-line bg-surface-2 p-2">
        <img
          v-if="imageUrl"
          :src="imageUrl"
          :alt="`Certificate image for ${title}`"
          width="1200"
          height="900"
          loading="lazy"
          decoding="async"
          class="max-h-[56vh] max-w-full object-contain transition-transform duration-200"
          :style="{ transform: `scale(${zoom})` }"
        />
      </div>
    </div>

    <template #footer>
      <BaseButton variant="secondary" size="sm" @click="close">Close</BaseButton>
    </template>
  </BaseModal>
</template>

<style scoped>
@media (prefers-reduced-motion: reduce) {
  img {
    transition: none;
  }
}
</style>