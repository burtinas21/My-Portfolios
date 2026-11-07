<!--
  SocialLinks.vue — Reusable Social Link Renderer
  ────────────────────────────────────────────────
  Renders all valid social links from the portfolio store as icons or buttons.
-->

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import { usePortfolioStore } from '@/stores/portfolio.js'
import { useToast } from '@/composables/useToast.js'
import { getSocialIcon, isEmailLink } from '@/utils/socialIcons.js'

const props = defineProps({
  variant: {
    type: String,
    default: 'icons',
    validator: (value) => ['icons', 'buttons'].includes(value),
  },
})

const portfolioStore = usePortfolioStore()
const toast = useToast()

const contactEmail = computed(() => portfolioStore.profile?.email || null)

const sortedSocials = computed(() => [...portfolioStore.socials]
  .filter((social) => isValidUrl(social.url))
  .sort((a, b) => Number(a.sort_order) - Number(b.sort_order) || Number(a.id) - Number(b.id)))

const visibleSocials = computed(() => sortedSocials.value.filter((social) => (
  props.variant !== 'buttons' || !isEmailLink(social)
)))

function isValidUrl(url) {
  if (typeof url !== 'string') return false

  const trimmed = url.trim()
  if (/^https?:\/\//i.test(trimmed)) {
    try {
      const parsed = new URL(trimmed)
      return ['http:', 'https:'].includes(parsed.protocol) && Boolean(parsed.hostname)
    } catch {
      return false
    }
  }

  if (/^mailto:/i.test(trimmed)) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+(?:\?.*)?$/i.test(trimmed.slice(trimmed.indexOf(':') + 1))
  }

  return /^tel:\+?[0-9().\-\s]+(?:;.*)?$/i.test(trimmed)
}

function socialName(social) {
  return social.name?.trim().toLowerCase() === 'twitter' ? 'X' : social.name
}

function isPrivateLink(url) {
  return /^(mailto:|tel:)/i.test(url.trim())
}

function openPrivateLink(social) {
  const url = social.url.trim()

  if (isEmailLink(social)) {
    const email = url.slice(url.indexOf(':') + 1).split('?')[0]
    let appOpened = false
    const markAppOpened = () => {
      appOpened = true
    }

    const handleVisibilityChange = () => {
      if (document.visibilityState === 'hidden') {
        markAppOpened()
      }
    }

    document.addEventListener('visibilitychange', handleVisibilityChange)
    window.addEventListener('blur', markAppOpened, { once: true })
    toast.success('Opening your email app')
    window.location.href = `mailto:${email}`
    window.setTimeout(async () => {
      document.removeEventListener('visibilitychange', handleVisibilityChange)
      window.removeEventListener('blur', markAppOpened)
      if (appOpened) return

      try {
        await navigator.clipboard.writeText(email)
        toast.success('Email copied')
      } catch {
        const fallbackArea = document.createElement('textarea')
        fallbackArea.value = email
        fallbackArea.setAttribute('readonly', '')
        fallbackArea.style.position = 'fixed'
        fallbackArea.style.opacity = '0'
        document.body.append(fallbackArea)
        fallbackArea.select()
        const copied = document.execCommand('copy')
        fallbackArea.remove()
        if (copied) {
          toast.success('Email copied')
        } else {
          toast.error('Could not open your email app or copy the address.')
        }
      }
    }, 1500)
    return
  }

  toast.success('Opening your phone app')
  window.location.href = url
}

function openContactEmail() {
  if (!contactEmail.value) return

  toast.success('Opening your email app')
  window.location.href = `mailto:${contactEmail.value}`
}
</script>

<template>
  <div v-if="variant === 'icons'" class="social-icons-row">
    <template v-for="social in visibleSocials" :key="social.id">
      <button
        v-if="isPrivateLink(social.url)"
        type="button"
        class="social-icon-control"
        :title="socialName(social)"
        :aria-label="socialName(social)"
        @click="openPrivateLink(social)"
      >
        <Icon :icon="getSocialIcon(social)" width="18" height="18" aria-hidden="true" />
      </button>
      <a
        v-else
        :href="social.url.trim()"
        target="_blank"
        rel="noopener noreferrer"
        class="social-icon-control"
        :title="socialName(social)"
        :aria-label="`${socialName(social)} (opens in a new tab)`"
      >
        <Icon :icon="getSocialIcon(social)" width="18" height="18" aria-hidden="true" />
      </a>
    </template>
  </div>

  <div v-else class="social-buttons-row">
    <button
      v-if="contactEmail"
      type="button"
      class="social-button social-button--filled"
      aria-label="Contact me via email"
      @click="openContactEmail"
    >
      <Icon icon="simple-icons:gmail" width="18" height="18" aria-hidden="true" />
      <span>Contact me</span>
    </button>

    <template v-for="social in visibleSocials" :key="social.id">
      <button
        v-if="isPrivateLink(social.url)"
        type="button"
        class="social-button social-button--outlined"
        :aria-label="socialName(social)"
        @click="openPrivateLink(social)"
      >
        <Icon :icon="getSocialIcon(social)" width="18" height="18" aria-hidden="true" />
        <span>{{ socialName(social) }}</span>
      </button>
      <a
        v-else
        :href="social.url.trim()"
        target="_blank"
        rel="noopener noreferrer"
        class="social-button social-button--outlined"
        :aria-label="`${socialName(social)} (opens in a new tab)`"
      >
        <Icon :icon="getSocialIcon(social)" width="18" height="18" aria-hidden="true" />
        <span>{{ socialName(social) }}</span>
      </a>
    </template>
  </div>
</template>

<style scoped>
@reference "@/assets/main.css";

.social-icons-row {
  @apply flex flex-wrap items-center gap-2;
}

.social-icon-control {
  @apply inline-flex h-11 w-11 items-center justify-center rounded-control
         border border-transparent text-muted hover-icon focus-ring;
}

.social-buttons-row {
  @apply flex flex-wrap gap-3 md:gap-4;
}

.social-button {
  @apply inline-flex min-h-11 items-center gap-3 rounded-control border
         px-5 py-3 text-sm font-medium focus-ring transition-colors;
}

.social-button--filled {
  @apply border-accent bg-accent text-on-accent hover-button-primary;
}

.social-button--outlined {
  @apply border-line bg-transparent text-fg hover-button-outlined;
}
</style>
