<!--
  BaseButton.vue — Reusable Button Component
  ───────────────────────────────────────────
  A consistent button used across the entire app (public + admin).

  Props:
  - variant: 'primary' (accent), 'secondary' (outlined), 'ghost' (text only), 'danger' (red)
  - size: 'sm', 'md', 'lg'
  - loading: shows a spinner and disables the button
  - disabled: visually and functionally disables the button

  Usage:
    <BaseButton variant="primary" @click="save">Save</BaseButton>
    <BaseButton variant="secondary" :loading="isSaving">Saving...</BaseButton>

  Used by: nearly every page and modal in the app
-->

<script setup>
const props = defineProps({
  /** Visual style: 'primary' | 'secondary' | 'ghost' | 'danger' */
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'ghost', 'danger'].includes(v),
  },
  /** Size: 'sm' | 'md' | 'lg' */
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v),
  },
  /** When true, shows a spinner and prevents clicks */
  loading: {
    type: Boolean,
    default: false,
  },
  /** When true, the button is greyed out and non-interactive */
  disabled: {
    type: Boolean,
    default: false,
  },
  /** HTML button type (button, submit, reset) */
  type: {
    type: String,
    default: 'button',
  },
})

/* Combine variant and size into Tailwind classes */
const variantClasses = {
  primary:
    'bg-accent text-on-accent hover-button-primary',
  secondary:
    'border border-line bg-transparent text-fg hover-button-outlined',
  ghost:
    'bg-transparent text-muted hover-button-outlined',
  danger:
    'bg-danger text-white hover-button-primary',
}

const sizeClasses = {
  sm: 'px-3 py-1.5 text-sm gap-1.5',
  md: 'px-5 py-2.5 text-sm gap-2',
  lg: 'px-7 py-3 text-base gap-2.5',
}
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[
      /* Base styles shared by all buttons */
      'inline-flex items-center justify-center font-medium',
      'rounded-control focus-ring',
      'disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100',
      /* Variant and size */
      variantClasses[variant],
      sizeClasses[size],
    ]"
  >
    <!-- Loading spinner (replaces the icon slot) -->
    <svg
      v-if="loading"
      class="h-4 w-4 animate-spin"
      viewBox="0 0 24 24"
      fill="none"
      aria-hidden="true"
    >
      <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25" />
      <path
        d="M4 12a8 8 0 018-8"
        stroke="currentColor"
        stroke-width="3"
        stroke-linecap="round"
        class="opacity-75"
      />
    </svg>

    <!-- Button content -->
    <slot />
  </button>
</template>
