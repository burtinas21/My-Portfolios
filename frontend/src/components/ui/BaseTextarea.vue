<!--
  BaseTextarea.vue — Reusable Textarea Component
  ───────────────────────────────────────────────
  Same API as BaseInput but for multi-line text.
  Shows a character count when maxlength is set.

  Usage:
    <BaseTextarea v-model="bio" label="Bio" :maxlength="2000" :error="errors.bio" />

  Used by: contact form (message body), admin profile (bio), admin projects (detail)
-->

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, default: '' },
  error: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  id: { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  /** Number of visible rows */
  rows: { type: Number, default: 4 },
  /** Max character count (shows counter when set) */
  maxlength: { type: Number, default: null },
})

const emit = defineEmits(['update:modelValue'])

function onInput(event) {
  emit('update:modelValue', event.target.value)
}

/** Current character count (for the counter display) */
const charCount = computed(() => (props.modelValue || '').length)
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" :for="id" class="text-sm font-medium text-fg">
      {{ label }}
      <span v-if="required" class="text-danger" aria-hidden="true">*</span>
    </label>

    <textarea
      :id="id"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :rows="rows"
      :maxlength="maxlength"
      :aria-invalid="!!error"
      :aria-describedby="error && id ? `${id}-error` : undefined"
      :class="[
        'w-full rounded-control border bg-surface px-3.5 py-2.5 text-sm text-fg resize-y',
        'placeholder:text-muted/60',
        'transition-colors duration-150',
        'focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-1 focus:ring-offset-bg',
        'disabled:opacity-50 disabled:cursor-not-allowed',
        error ? 'border-danger' : 'border-line hover:border-muted',
      ]"
      @input="onInput"
    />

    <!-- Bottom row: error message and character counter -->
    <div class="flex items-center justify-between">
      <p
        v-if="error"
        :id="id ? `${id}-error` : undefined"
        class="text-xs text-danger"
        role="alert"
      >
        {{ error }}
      </p>
      <span v-else />

      <span
        v-if="maxlength"
        class="text-xs text-muted"
        :class="{ 'text-danger': charCount > maxlength }"
      >
        {{ charCount }} / {{ maxlength }}
      </span>
    </div>
  </div>
</template>
