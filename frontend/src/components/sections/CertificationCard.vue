<!--
  CertificationCard.vue — Public Credential Card
  ───────────────────────────────────────────────
  Presents issuer, dates, credential details, and optional verification media.
-->

<script setup>
import { computed, defineAsyncComponent, ref, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { CalendarDays, Copy, ExternalLink } from 'lucide-vue-next'
import { useReveal } from '@/composables/useReveal.js'
import { useToast } from '@/composables/useToast.js'
import { assetUrl } from '@/utils/url.js'

const CertificationImageLightbox = defineAsyncComponent(
  () => import('@/components/sections/CertificationImageLightbox.vue'),
)

const props = defineProps({
  certification: { type: Object, required: true },
  revealDelay: { type: Number, default: 0 },
})

const toast = useToast()
const { revealRef } = useReveal()
const iconFailed = ref(false)
const lightboxOpen = ref(false)

watch(() => props.certification.issuer_icon, () => {
  iconFailed.value = false
})

const imageUrl = computed(() => assetUrl(props.certification.image))

function formatMonthYear(value) {
  if (!value) return ''
  const date = String(value).slice(0, 10)
  return new Intl.DateTimeFormat(undefined, { month: 'short', year: 'numeric' })
    .format(new Date(`${date}T00:00:00`))
}

async function copyCredentialId() {
  try {
    await navigator.clipboard.writeText(props.certification.credential_id)
    toast.success('Credential ID copied')
  } catch {
    toast.error('Credential ID could not be copied.')
  }
}
</script>

<template>
  <div ref="revealRef" class="h-full">
    <article
      class="reveal flex h-full min-h-[18rem] flex-col rounded-2xl border border-line bg-surface p-6 shadow-card hover-card"
      :style="{ transitionDelay: `${revealDelay}ms` }"
    >
      <div class="flex items-center gap-3">
        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-line bg-white">
          <Icon
            v-if="!iconFailed"
            :icon="certification.issuer_icon || 'lucide:award'"
            width="26"
            height="26"
            aria-hidden="true"
            @error="iconFailed = true"
          />
          <Icon v-else icon="lucide:award" width="26" height="26" aria-hidden="true" />
        </span>
        <p class="min-w-0 truncate text-xs font-semibold uppercase text-muted" :title="certification.issuer">
          {{ certification.issuer }}
        </p>
      </div>

      <h3 class="mt-5 line-clamp-3 text-lg font-semibold leading-snug text-fg">
        {{ certification.title }}
      </h3>

      <div class="mt-3 flex flex-wrap items-center gap-x-1.5 gap-y-2 text-sm text-muted">
        <CalendarDays class="h-4 w-4 shrink-0" aria-hidden="true" />
        <span>Issued {{ formatMonthYear(certification.issue_date) }}</span>
        <span v-if="certification.expiry_date && !certification.is_expired">
          · Expires {{ formatMonthYear(certification.expiry_date) }}
        </span>
        <span v-if="certification.is_expired" class="rounded-control bg-surface-2 px-2 py-0.5 text-xs text-muted">
          Expired
        </span>
      </div>

      <p v-if="certification.description" class="mt-3 line-clamp-2 text-sm leading-relaxed text-muted">
        {{ certification.description }}
      </p>

      <div
        v-if="certification.credential_id || certification.credential_url || imageUrl"
        class="mt-auto flex flex-wrap items-center justify-between gap-3 border-t border-line pt-4"
      >
        <div v-if="certification.credential_id" class="flex min-w-0 items-center gap-1">
          <span class="break-all font-mono text-xs text-muted">{{ certification.credential_id }}</span>
          <button
            type="button"
            class="min-h-11 min-w-11 shrink-0 rounded-control text-muted hover-icon focus-ring"
            aria-label="Copy credential ID"
            title="Copy credential ID"
            @click="copyCredentialId"
          >
            <Copy class="mx-auto h-4 w-4" aria-hidden="true" />
          </button>
        </div>

        <div class="ml-auto flex flex-wrap items-center justify-end gap-2">
          <button
            v-if="imageUrl"
            type="button"
            class="min-h-11 rounded-control px-2 text-sm font-medium text-accent hover-link focus-ring"
            @click="lightboxOpen = true"
          >
            View certificate
          </button>
          <a
            v-if="certification.credential_url"
            :href="certification.credential_url"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex min-h-11 items-center gap-1 rounded-control px-2 text-sm font-medium text-accent hover-link focus-ring"
            :aria-label="`Verify ${certification.title} (opens in a new tab)`"
          >
            <span>Verify</span>
            <ExternalLink class="h-4 w-4" aria-hidden="true" />
          </a>
        </div>
      </div>

      <CertificationImageLightbox
        v-if="imageUrl && lightboxOpen"
        v-model:open="lightboxOpen"
        :image="certification.image"
        :title="certification.title"
      />
    </article>
  </div>
</template>