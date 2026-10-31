<!--
  ConfirmDialog.vue — Reusable Destructive Action Confirmation
  ─────────────────────────────────────────────────────────────
  Shows a modal asking the user to confirm a dangerous action (e.g. delete).

  Props:
  - open: v-model:open for visibility
  - title: dialog heading
  - message: descriptive text explaining what will happen
  - confirmLabel: text for the confirm button (default "Delete")
  - loading: shows spinner on confirm button while request is in-flight

  Events:
  - confirm: fired when the user clicks the confirm button
  - cancel / update:open=false: fired on cancel or backdrop click

  Usage:
    <ConfirmDialog
      v-model:open="showConfirm"
      title="Delete Project"
      message="This project will be permanently deleted."
      :loading="isDeleting"
      @confirm="doDelete"
    />
-->

<script setup>
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: 'Are you sure?' },
  message: { type: String, default: 'This action cannot be undone.' },
  confirmLabel: { type: String, default: 'Delete' },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['update:open', 'confirm'])

function cancel() {
  emit('update:open', false)
}

function confirm() {
  emit('confirm')
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="title"
    max-width="max-w-md"
    @update:open="$emit('update:open', $event)"
  >
    <!-- Warning icon + message -->
    <div class="flex flex-col items-center text-center gap-4 py-2">
      <div class="w-14 h-14 rounded-full bg-danger/10 text-danger flex items-center justify-center">
        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
          <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
      </div>
      <p class="text-sm text-muted max-w-xs leading-relaxed">{{ message }}</p>
    </div>

    <template #footer>
      <BaseButton variant="secondary" size="sm" @click="cancel">Cancel</BaseButton>
      <BaseButton
        variant="danger"
        size="sm"
        :loading="loading"
        @click="confirm"
      >
        {{ confirmLabel }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
