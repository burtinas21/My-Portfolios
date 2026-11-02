<!--
  SocialLinks.vue — Reusable Social Link Renderer
  ────────────────────────────────────────────────
  Renders social links from the portfolio store in two visual styles:

  variant="icons"  (default) — Small circular icon-only buttons, used in the
                    hero section and footer. Same layout/sizing as the old inline SVGs.

  variant="buttons" — Rectangular icon + label buttons matching the reference design.
                    Used in the Contact section. Includes a filled "Contact me" email
                    button with privacy protection (mailto built in click handler only).

  Props:
    variant  — "icons" | "buttons" (default: "icons")

  Used by: HeroSection.vue, TheFooter.vue, ContactSection.vue
-->

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import { usePortfolioStore } from '@/stores/portfolio.js'
import { useToast } from '@/composables/useToast.js'
import { getSocialIcon, isEmailLink } from '@/utils/socialIcons.js'

const props = defineProps({
  /**
   * Visual variant:
   * - "icons":   compact icon-only row (hero / footer)
   * - "buttons": rectangular icon + label row (contact section)
   */
  variant: {
    type: String,
    default: 'icons',
    validator: (v) => ['icons', 'buttons'].includes(v),
  },
})

const portfolioStore = usePortfolioStore()
const toast = useToast()

/* ── Computed: the email address (for the "Contact me" button) ── */

/**
 * Resolve the contact email address:
 * 1. portfolioStore.profile.email (preferred)
 * 2. Fall back to a mailto: entry in the socials list
 * Returns null if neither exists → the email button is hidden.
 */
const contactEmail = computed(() => {
  // Primary source: profile.email
  const profileEmail = portfolioStore.profile?.email
  if (profileEmail) return profileEmail

  // Fallback: find a mailto: social link and extract the address
  const emailSocial = portfolioStore.socials.find((s) => isEmailLink(s))
  if (emailSocial) {
    const url = (emailSocial.url || '').trim()
    if (url.startsWith('mailto:')) return url.replace('mailto:', '').split('?')[0]
    // Plain email address in URL field
    if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(url)) return url
  }

  return null
})

/* ── Computed: non-email social links (for both variants) ─────── */

/**
 * Filter out email entries from the socials list so they don't
 * appear alongside the dedicated "Contact me" button.
 * Also skip links with missing or invalid URLs.
 */
const filteredSocials = computed(() => {
  return portfolioStore.socials.filter((s) => {
    // Skip email entries (they're handled by the Contact me button)
    if (isEmailLink(s)) return false
    // Skip entries with no URL
    const url = (s.url || '').trim()
    if (!url) return false
    return true
  })
})

/* ── Email button click handler (privacy-first) ──────────────── */

/**
 * Handle the "Contact me" button click.
 * Builds the mailto: URI only inside this handler — the email address
 * never appears in the rendered HTML, protecting it from scrapers.
 *
 * Strategy:
 * 1. Open mailto: link via window.location.href
 * 2. Show "Opening your email app" toast
 * 3. After 1.5s, if focus hasn't left the page (mail app didn't open),
 *    copy the email to clipboard as a fallback with "Email copied" toast
 */
function handleEmailClick() {
  const email = contactEmail.value
  if (!email) return

  toast.success('Opening your email app')

  // Build the mailto URI only at the moment of interaction
  window.location.href = `mailto:${email}`

  // Clipboard fallback: if the mail client didn't open (user stays on page),
  // copy the email address after a short delay
  setTimeout(async () => {
    try {
      await navigator.clipboard.writeText(email)
      toast.success('Email copied')
    } catch {
      // Clipboard API not available — fail silently
    }
  }, 1500)
}

/**
 * Check if a URL is valid for navigation (starts with http/https/mailto/tel).
 * Prevents crashes from malformed URLs.
 *
 * @param {string} url
 * @returns {boolean}
 */
function isValidUrl(url) {
  const trimmed = (url || '').trim()
  return /^(https?:\/\/|mailto:|tel:)/i.test(trimmed)
}
</script>

<template>
  <!-- ═══════════════════════════════════════════════════════════ -->
  <!-- VARIANT: "icons" — compact icon-only row (hero / footer)  -->
  <!-- ═══════════════════════════════════════════════════════════ -->
  <div v-if="variant === 'icons'" class="social-icons-row">
    <template v-for="social in filteredSocials" :key="social.id">
      <a
        v-if="isValidUrl(social.url)"
        :href="social.url"
        target="_blank"
        rel="noopener noreferrer"
        :title="social.name"
        :aria-label="`${social.name} (opens in a new tab)`"
        class="social-icon-link"
      >
        <Icon
          :icon="getSocialIcon(social)"
          width="16"
          height="16"
          aria-hidden="true"
        />
      </a>
    </template>
  </div>

  <!-- ═══════════════════════════════════════════════════════════ -->
  <!-- VARIANT: "buttons" — rectangular label buttons (contact)  -->
  <!-- ═══════════════════════════════════════════════════════════ -->
  <div v-else class="social-buttons-row stagger">
    <!-- "Contact me" email button — FILLED, always first -->
    <button
      v-if="contactEmail"
      type="button"
      class="social-btn social-btn--filled reveal"
      @click="handleEmailClick"
      aria-label="Contact me via email"
    >
      <Icon icon="lucide:mail" width="18" height="18" aria-hidden="true" />
      <span>Contact me</span>
    </button>

    <!-- Other social links — OUTLINED, in sort order -->
    <template v-for="social in filteredSocials" :key="social.id">
      <a
        v-if="isValidUrl(social.url)"
        :href="social.url"
        target="_blank"
        rel="noopener noreferrer"
        :aria-label="`${social.name} (opens in a new tab)`"
        class="social-btn social-btn--outlined reveal"
      >
        <Icon
          :icon="getSocialIcon(social)"
          width="18"
          height="18"
          aria-hidden="true"
        />
        <span>{{ social.name }}</span>
      </a>
    </template>
  </div>
</template>

<style scoped>
@reference "@/assets/main.css";

/* ── Icons variant (hero / footer) ────────────────────────────── */
/* Matches the original inline icon row: 36×36px, rounded, subtle border on hover */

.social-icons-row {
  @apply flex items-center gap-2;
}

.social-icon-link {
  @apply w-9 h-9 rounded-control
         flex items-center justify-center
         text-muted border border-transparent
         hover-icon focus-ring;
}

/* ── Buttons variant (contact section) ────────────────────────── */
/* Rectangular buttons: icon + uppercase monospace label */

.social-buttons-row {
  @apply flex flex-wrap gap-3 md:gap-4;
}

/* Shared button base */
.social-btn {
  @apply inline-flex items-center gap-3
         px-5 py-3
         rounded-sm
         text-xs sm:text-sm
         uppercase tracking-wider font-medium
         cursor-pointer select-none
         focus-ring;
  font-family: var(--font-mono);
  min-height: 44px; /* touch target */
  border: 1px solid transparent;
  text-decoration: none;
  transition: opacity var(--transition-hover),
              border-color var(--transition-hover);
}

/* Filled variant (Contact me button): bg-fg text-bg */
.social-btn--filled {
  @apply bg-fg text-bg border-fg;
}

@media (hover: hover) {
  .social-btn--filled:hover {
    opacity: 0.85;
  }
}

.social-btn--filled:active {
  opacity: 0.75;
}

/* Outlined variant (other socials): border-line text-fg */
.social-btn--outlined {
  @apply bg-transparent text-fg border-line;
}

@media (hover: hover) {
  .social-btn--outlined:hover {
    @apply border-fg;
  }
}

.social-btn--outlined:active {
  opacity: 0.8;
}
</style>
