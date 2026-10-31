<!--
  BaseModal.vue — Reusable Modal/Dialog Component
  ────────────────────────────────────────────────
  An accessible modal overlay with:
  - Backdrop click to close
  - Escape key to close
  - Focus trap (keeps Tab cycling inside the modal)
  - Restores focus to the trigger element on close
  - Transition animations (defined in main.css)

  Props:
  - open: controls visibility (v-model pattern)
  - title: heading text in the modal header
  - maxWidth: max width class (default 'max-w-lg')

  Slots:
  - default: modal body content
  - footer: optional footer with action buttons

  Usage:
    <BaseModal v-model:open="showModal" title="Edit Project">
      <p>Modal content here</p>
      <template #footer>
        <BaseButton @click="save">Save</BaseButton>
      </template>
    </BaseModal>

  Used by: project detail, confirm dialog, admin forms
-->

<script setup>
import { watch, nextTick, ref, onUnmounted } from 'vue'

const props = defineProps({
  /** Whether the modal is open */
  open: { type: Boolean, default: false },
  /** Title shown in the modal header */
  title: { type: String, default: '' },
  /** Max width Tailwind class */
  maxWidth: { type: String, default: 'max-w-lg' },
})

const emit = defineEmits(['update:open'])

/** Reference to the modal panel for focus management */
const panelRef = ref(null)

/** The element that had focus before the modal opened */
let previouslyFocused = null

/**
 * Close the modal and restore focus to the trigger element.
 */
function close() {
  emit('update:open', false)
}

/**
 * Handle keyboard events inside the modal.
 * - Escape closes the modal
 * - Tab is trapped inside (wraps from last to first focusable element)
 */
function onKeydown(event) {
  if (event.key === 'Escape') {
    close()
    return
  }

  // Focus trap: keep Tab cycling inside the modal
  if (event.key === 'Tab' && panelRef.value) {
    const focusable = panelRef.value.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
    )
    const first = focusable[0]
    const last = focusable[focusable.length - 1]

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault()
      last?.focus()
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault()
      first?.focus()
    }
  }
}

/* When the modal opens, save the current focus and move focus into the modal */
watch(
  () => props.open,
  async (isOpen) => {
    if (isOpen) {
      previouslyFocused = document.activeElement
      await nextTick()
      // Focus the first focusable element inside the modal
      const firstFocusable = panelRef.value?.querySelector(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
      )
      firstFocusable?.focus()
    } else {
      // Restore focus to the element that opened the modal
      previouslyFocused?.focus()
      previouslyFocused = null
    }
  },
)
</script>

<template>
  <Teleport to="body">
    <!-- Backdrop -->
    <Transition name="backdrop">
      <div
        v-if="open"
        class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
        aria-hidden="true"
        @click="close"
      />
    </Transition>

    <!-- Modal panel -->
    <Transition name="modal">
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
        @keydown="onKeydown"
      >
        <div
          ref="panelRef"
          :class="[
            'w-full bg-surface rounded-card shadow-overlay',
            'max-h-[85vh] flex flex-col',
            maxWidth,
          ]"
          @click.stop
        >
          <!-- Header -->
          <div
            v-if="title"
            class="flex items-center justify-between px-6 py-4 border-b border-line"
          >
            <h2 class="text-lg font-semibold text-fg">{{ title }}</h2>
            <button
              class="rounded-control p-1.5 text-muted hover:text-fg hover:bg-surface-2 transition-colors"
              aria-label="Close dialog"
              @click="close"
            >
              <!-- X icon -->
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M18 6 6 18M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Body (scrollable if content is tall) -->
          <div class="flex-1 overflow-y-auto px-6 py-4">
            <slot />
          </div>

          <!-- Footer (optional) -->
          <div
            v-if="$slots.footer"
            class="flex items-center justify-end gap-3 px-6 py-4 border-t border-line"
          >
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
