<!--
  BaseInput.vue — Reusable Text Input Component
  ──────────────────────────────────────────────
  A styled input field with label, error message, and icon slot.

  Props:
  - modelValue: the v-model value (Vue 3 convention)
  - label: visible label text
  - error: error message to display below the input (from 422 validation)
  - type: input type (text, email, password, number, etc.)
  - placeholder, id, required, disabled

  Usage:
    <BaseInput v-model="email" label="Email" type="email" :error="errors.email" />

  Used by: admin forms, login form, contact form
-->

<script setup>
const props = defineProps({
  /** The input value (for v-model) */
  modelValue: {
    type: [String, Number],
    default: '',
  },
  /** Visible label text above the input */
  label: {
    type: String,
    default: '',
  },
  /** Error message displayed below the input (turns the border red) */
  error: {
    type: String,
    default: '',
  },
  /** HTML input type */
  type: {
    type: String,
    default: 'text',
  },
  /** Placeholder text inside the input */
  placeholder: {
    type: String,
    default: '',
  },
  /** HTML id attribute (also used for label's "for" attribute) */
  id: {
    type: String,
    default: '',
  },
  /** Whether the field is required */
  required: {
    type: Boolean,
    default: false,
  },
  /** Whether the field is disabled */
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue'])

/**
 * Update the parent's v-model when the user types.
 * This is the standard Vue 3 pattern for component v-model.
 */
function onInput(event) {
  emit('update:modelValue', event.target.value)
}
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <!-- Label -->
    <label
      v-if="label"
      :for="id"
      class="text-sm font-medium text-fg"
    >
      {{ label }}
      <span v-if="required" class="text-danger" aria-hidden="true">*</span>
    </label>

    <!-- Input field -->
    <input
      :id="id"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :aria-invalid="!!error"
      :aria-describedby="error && id ? `${id}-error` : undefined"
      :class="[
        'w-full rounded-control border bg-surface px-3.5 py-2.5 text-sm text-fg',
        'placeholder:text-muted/60',
        'transition-colors duration-150',
        'focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-1 focus:ring-offset-bg',
        'disabled:opacity-50 disabled:cursor-not-allowed',
        /* Red border when there's an error, normal border otherwise */
        error ? 'border-danger' : 'border-line hover:border-muted',
      ]"
      @input="onInput"
    />

    <!-- Error message -->
    <p
      v-if="error"
      :id="id ? `${id}-error` : undefined"
      class="text-xs text-danger"
      role="alert"
    >
      {{ error }}
    </p>
  </div>
</template>
