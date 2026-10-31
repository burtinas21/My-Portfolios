<!--
  TagInput.vue — Comma/Enter-separated tag input
  ───────────────────────────────────────────────
  A controlled input for managing an array of string tags (e.g. project stack).

  Props:
  - modelValue: string[] — the current tag array (v-model)
  - label: input label text
  - placeholder: placeholder for the text input
  - maxTags: max number of tags allowed (default: 20)

  Events:
  - update:modelValue: emits the new tag array on every change

  Usage:
    <TagInput v-model="form.stack" label="Tech Stack" placeholder="Add technology..." />
-->

<script setup>
import { ref } from 'vue'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  label: { type: String, default: 'Tags' },
  placeholder: { type: String, default: 'Type and press Enter...' },
  maxTags: { type: Number, default: 20 },
})

const emit = defineEmits(['update:modelValue'])

const inputValue = ref('')

function addTag() {
  const raw = inputValue.value.trim()
  if (!raw) return

  // Support comma-separated input (e.g. pasting "Vue, Laravel, PHP")
  const newTags = raw
    .split(',')
    .map((t) => t.trim())
    .filter((t) => t.length > 0 && !props.modelValue.includes(t))

  if (newTags.length === 0) {
    inputValue.value = ''
    return
  }

  const merged = [...props.modelValue, ...newTags].slice(0, props.maxTags)
  emit('update:modelValue', merged)
  inputValue.value = ''
}

function removeTag(tag) {
  emit('update:modelValue', props.modelValue.filter((t) => t !== tag))
}

function onKeydown(e) {
  if (e.key === 'Enter') {
    e.preventDefault()
    addTag()
  } else if (e.key === 'Backspace' && !inputValue.value && props.modelValue.length) {
    removeTag(props.modelValue[props.modelValue.length - 1])
  }
}
</script>

<template>
  <div class="space-y-1.5">
    <label class="block text-sm font-medium text-fg">
      {{ label }}
    </label>

    <!-- Tag pills + input field -->
    <div
      class="flex flex-wrap gap-2 items-center min-h-[42px] px-3 py-2
             rounded-control border border-line bg-surface
             focus-within:ring-2 focus-within:ring-accent focus-within:border-accent
             transition-colors"
    >
      <!-- Existing tags -->
      <span
        v-for="tag in modelValue"
        :key="tag"
        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full
               text-xs font-medium bg-accent-soft text-accent"
      >
        {{ tag }}
        <button
          type="button"
          class="ml-0.5 text-accent/70 hover:text-accent transition-colors"
          :aria-label="`Remove ${tag}`"
          @click="removeTag(tag)"
        >
          <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M18 6 6 18M6 6l12 12" stroke-linecap="round"/>
          </svg>
        </button>
      </span>

      <!-- Text input -->
      <input
        v-if="modelValue.length < maxTags"
        v-model="inputValue"
        type="text"
        :placeholder="modelValue.length === 0 ? placeholder : ''"
        class="flex-1 min-w-[120px] bg-transparent text-sm text-fg
               placeholder:text-muted outline-none"
        @keydown="onKeydown"
        @blur="addTag"
      />
    </div>

    <p class="text-xs text-muted">Press Enter or comma to add. Backspace to remove last.</p>
  </div>
</template>
