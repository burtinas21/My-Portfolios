<!--
  ChatWidget.vue — Floating AI Assistant Chat Widget
  ──────────────────────────────────────────────────
  A friendly, accessible chat widget for non-technical visitors.
  
  Key features:
  - Pill launcher button with icon + text ("Ask me anything")
  - Speech bubble hint after 8 seconds (once per session)
  - Desktop: 24rem card, Mobile: bottom sheet
  - Auto-growing textarea (1-4 lines)
  - Character counter only shows when > 400/500 chars
  - Friendly error messages (no technical jargon)
  - Focus trap, aria-live, 44px min touch targets
  - Themed scrollbar, respects prefers-reduced-motion
  
  Used by: PublicLayout.vue (lazy-loaded)
-->

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { Icon } from '@iconify/vue'
import { useChatStore } from '@/stores/chat.js'
import { usePortfolioStore } from '@/stores/portfolio.js'

const chatStore = useChatStore()
const portfolioStore = usePortfolioStore()
const props = defineProps({
  initiallyOpen: { type: Boolean, default: false },
})
const emit = defineEmits(['ready'])

/* ── Local State ───────────────────────────────────────────────── */

const inputText = ref('')
const messageListRef = ref(null)
const inputRef = ref(null)
const panelRef = ref(null)
const launcherRef = ref(null)
const textareaRef = ref(null)

/** Speech bubble visibility */
const showSpeechBubble = ref(false)

/** Which element had focus before panel opened */
let previouslyFocused = null

/** Session storage key for speech bubble dismissal */
const BUBBLE_DISMISSED_KEY = 'chat_bubble_dismissed'

/* ── Computed ──────────────────────────────────────────────────── */

/**
 * Profile data for display
 */
const profile = computed(() => portfolioStore.profile)

/**
 * First name only (for "Ask about Jane" title)
 */
const firstName = computed(() => {
  const name = profile.value?.name || ''
  return name.split(' ')[0] || 'me'
})

/**
 * Initials for avatar fallback
 */
const initials = computed(() => profile.value?.initials || '?')

/**
 * Profile avatar URL
 */
const avatar = computed(() => {
  if (profile.value?.avatar?.startsWith('http')) {
    return profile.value.avatar
  }
  if (profile.value?.avatar) {
    return import.meta.env.VITE_API_URL.replace('/api', '') + profile.value.avatar
  }
  return null
})

/**
 * Character count for input field
 */
const charCount = computed(() => inputText.value.length)

/**
 * Whether to show character counter (only when > 400 chars)
 */
const showCharCount = computed(() => charCount.value > 400)

/**
 * Whether the send button should be enabled
 */
const canSend = computed(() => {
  return (
    inputText.value.trim().length > 0 &&
    !chatStore.chatLoading &&
    charCount.value <= 500
  )
})

/**
 * Is the input valid (not over 500 chars)
 */
const inputValid = computed(() => charCount.value <= 500)

/**
 * Message list for display (includes greeting as first message)
 */
const displayMessages = computed(() => {
  const msgs = []
  if (chatStore.greeting) {
    msgs.push({
      role: 'assistant',
      content: chatStore.greeting,
      isGreeting: true,
    })
  }
  return [...msgs, ...chatStore.messages]
})

/**
 * Check if device is touch-enabled
 */
const isTouchDevice = computed(() => {
  return 'ontouchstart' in window || navigator.maxTouchPoints > 0
})

/* ── Speech Bubble Logic ───────────────────────────────────────── */

/**
 * Show speech bubble after 8 seconds if not dismissed before
 */
function scheduleSpeechBubble() {
  // Check if user prefers reduced motion
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  if (prefersReducedMotion) return

  // Check if already dismissed this session
  const dismissed = sessionStorage.getItem(BUBBLE_DISMISSED_KEY)
  if (dismissed) return

  // Show after 8 seconds
  setTimeout(() => {
    if (!chatStore.isOpen && chatStore.isVisible) {
      showSpeechBubble.value = true
      
      // Auto-dismiss after 8 more seconds
      setTimeout(() => {
        showSpeechBubble.value = false
      }, 8000)
    }
  }, 8000)
}

/**
 * Dismiss speech bubble and remember dismissal
 */
function dismissSpeechBubble() {
  showSpeechBubble.value = false
  sessionStorage.setItem(BUBBLE_DISMISSED_KEY, 'true')
}

/* ── Watchers ──────────────────────────────────────────────────── */

/**
 * Auto-grow textarea as user types (max 4 lines)
 */
watch(inputText, () => {
  if (!textareaRef.value) return
  
  // Reset height to recalculate
  textareaRef.value.style.height = 'auto'
  
  // Calculate new height (max 4 lines ≈ 96px)
  const scrollHeight = textareaRef.value.scrollHeight
  const maxHeight = 96 // 4 lines
  textareaRef.value.style.height = Math.min(scrollHeight, maxHeight) + 'px'
})

/**
 * When messages update, scroll to bottom and focus input
 */
watch(
  () => chatStore.messages.length,
  async () => {
    await nextTick()
    scrollToBottom()
    
    // Focus input after receiving a reply
    if (!chatStore.chatLoading && textareaRef.value) {
      textareaRef.value.focus()
    }
  },
)

/**
 * When panel opens/closes, manage focus and body scroll
 */
watch(
  () => chatStore.isOpen,
  async (isOpen) => {
    if (isOpen) {
      previouslyFocused = document.activeElement
      document.body.style.overflow = 'hidden'
      
      // Dismiss speech bubble if open
      if (showSpeechBubble.value) {
        dismissSpeechBubble()
      }
      
      await nextTick()
      textareaRef.value?.focus()
    } else {
      document.body.style.overflow = ''
      
      // Return focus to launcher button
      if (launcherRef.value) {
        launcherRef.value.focus()
      } else if (previouslyFocused) {
        previouslyFocused.focus()
      }
      previouslyFocused = null
    }
  },
)

/* ── Methods ───────────────────────────────────────────────────── */

/**
 * Send the current input as a message
 */
async function handleSend() {
  if (!canSend.value) return

  const message = inputText.value
  inputText.value = ''
  
  // Reset textarea height
  if (textareaRef.value) {
    textareaRef.value.style.height = 'auto'
  }
  
  await chatStore.sendMessage(message)
  if (chatStore.chatError) {
    inputText.value = message
    textareaRef.value?.focus()
  }
}

/**
 * Handle keydown in textarea
 * - Desktop: Enter sends, Shift+Enter new line
 * - Touch: Enter always new line, Send button sends
 */
function handleKeydown(event) {
  if (event.key === 'Escape') {
    chatStore.close()
    return
  }

  // On touch devices, Enter always makes new line
  if (isTouchDevice.value) {
    return
  }

  // On desktop, Enter sends (unless Shift is held)
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    if (canSend.value) {
      handleSend()
    }
  }
}

/**
 * Handle suggested question click
 */
async function handleSuggestedClick(question) {
  await chatStore.askSuggestedQuestion(question)
  if (chatStore.chatError) {
    inputText.value = question
    textareaRef.value?.focus()
  }
}

/**
 * Auto-scroll to bottom of message list
 */
function scrollToBottom() {
  if (!messageListRef.value) return
  messageListRef.value.scrollTop = messageListRef.value.scrollHeight
}

/**
 * Close the panel
 */
function handleClose() {
  chatStore.close()
}

/**
 * Scroll to contact section and close widget
 */
function handleContactClick() {
  const contactElement = document.getElementById('contact')
  if (contactElement) {
    contactElement.scrollIntoView({ behavior: 'smooth' })
    chatStore.close()
  }
}

/**
 * Retry after network error
 */
async function handleRetry() {
  await handleSend()
}

/**
 * Handle keyboard focus trap inside panel
 */
function handlePanelKeydown(event) {
  if (event.key === 'Escape') {
    handleClose()
    return
  }

  if (event.key !== 'Tab' || !panelRef.value) return

  const focusable = panelRef.value.querySelectorAll(
    'button:not([disabled]), [href], input:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
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

/* ── Lifecycle ─────────────────────────────────────────────────── */

onMounted(async () => {
  chatStore.loadMessages()
  
  // Fetch config fresh (don't rely on cache)
  await chatStore.fetchConfig()

  if (props.initiallyOpen && chatStore.isVisible) {
    chatStore.open()
  }
  emit('ready')
  
  // Schedule speech bubble if widget is visible
  if (chatStore.isVisible && !chatStore.isOpen) {
    scheduleSpeechBubble()
  }
})

onUnmounted(() => {
  document.body.style.overflow = ''
})
</script>

<template>
  <!-- Only render if enabled and config loaded -->
  <div v-if="chatStore.isVisible" class="chat-widget">
    <!-- Launcher button (pill with icon + text) -->
    <button
      ref="launcherRef"
      class="fixed z-[9999] flex items-center gap-2 px-4 py-3 rounded-full bg-accent text-on-accent shadow-overlay hover-button-primary focus-ring transition-all"
      style="bottom: max(1rem, env(safe-area-inset-bottom, 1rem)); right: 1rem; min-height: 48px;"
      :aria-label="`Ask me anything about ${firstName}`"
      @click="chatStore.toggle"
    >
      <Icon icon="lucide:message-circle" class="w-5 h-5 flex-shrink-0" />
      <span class="font-medium text-sm hidden xs:inline">Ask me anything</span>
      <span class="font-medium text-sm xs:hidden">Ask me</span>
    </button>

    <!-- Speech bubble (appears once after 8 seconds) -->
    <Transition name="speech-bubble">
      <div
        v-if="showSpeechBubble"
        class="fixed z-[10000] bg-surface border border-line rounded-lg shadow-overlay px-3 py-2 text-sm text-fg"
        style="bottom: calc(max(1rem, env(safe-area-inset-bottom, 1rem)) + 60px); right: 1rem; max-width: 200px;"
        role="status"
        aria-live="polite"
      >
        <button
          class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-surface border border-line flex items-center justify-center text-muted hover-icon focus-ring"
          aria-label="Dismiss hint"
          @click="dismissSpeechBubble"
        >
          <Icon icon="lucide:x" class="w-3 h-3" />
        </button>
        <p>Hi! Ask me about my work.</p>
      </div>
    </Transition>

    <!-- Backdrop -->
    <Transition name="chat-backdrop">
      <div
        v-if="chatStore.isOpen"
        class="fixed inset-0 z-[9998] bg-black/30 backdrop-blur-sm sm:bg-transparent"
        aria-hidden="true"
        @click="handleClose"
      />
    </Transition>

    <!-- Chat panel -->
    <Transition name="chat-panel">
      <div
        v-if="chatStore.isOpen"
        ref="panelRef"
        class="chat-panel fixed inset-0 z-[9999] sm:bottom-auto sm:left-auto sm:top-auto sm:right-4 sm:w-[24rem] sm:rounded-2xl bg-surface border border-line shadow-overlay flex flex-col"
        style="
          bottom: 0;
          height: 100dvh;
          max-height: 100dvh;
        "
        role="dialog"
        aria-modal="true"
        :aria-labelledby="`chat-title-${firstName}`"
        @keydown="handlePanelKeydown"
      >
        <!-- Header -->
        <div class="flex items-center gap-3 px-4 py-3 border-b border-line flex-shrink-0">
          <!-- Avatar -->
          <div class="w-9 h-9 rounded-full bg-accent-soft flex items-center justify-center text-accent font-semibold text-sm overflow-hidden flex-shrink-0">
            <img v-if="avatar" :src="avatar" :alt="firstName" width="36" height="36" loading="lazy" decoding="async" class="w-full h-full object-cover" />
            <span v-else>{{ initials }}</span>
          </div>

          <!-- Title & subtitle -->
          <div class="flex-1 min-w-0">
            <h2 :id="`chat-title-${firstName}`" class="text-base font-semibold text-fg">
              Ask me anything
            </h2>
            <p class="text-xs text-muted truncate">
              AI assistant for {{ firstName }}
            </p>
          </div>

          <!-- Close button -->
          <button
            class="w-11 h-11 rounded-full flex items-center justify-center text-muted hover-icon focus-ring flex-shrink-0"
            aria-label="Close chat"
            @click="handleClose"
          >
            <Icon icon="lucide:x" class="w-5 h-5" />
          </button>
        </div>

        <!-- Message list (scrollable, themed scrollbar) -->
        <div
          ref="messageListRef"
          class="flex-1 min-h-0 overflow-y-auto px-4 py-4 space-y-3 chat-scrollbar"
          aria-live="polite"
          aria-label="Chat messages"
        >
          <!-- Messages -->
          <div
            v-for="(message, index) in displayMessages"
            :key="`msg-${index}`"
            :class="[
              'flex gap-2',
              message.role === 'user' ? 'justify-end' : 'justify-start',
            ]"
          >
            <!-- Assistant message (with small avatar) -->
            <div v-if="message.role === 'assistant'" class="flex gap-2 max-w-[85%]">
              <div class="w-6 h-6 rounded-full bg-accent-soft flex items-center justify-center text-accent text-xs font-semibold overflow-hidden flex-shrink-0">
                <img v-if="avatar" :src="avatar" :alt="firstName" width="24" height="24" loading="lazy" decoding="async" class="w-full h-full object-cover" />
                <span v-else>{{ initials }}</span>
              </div>
              <div class="bg-surface-2 rounded-2xl px-4 py-2 text-sm text-fg">
                <p class="whitespace-pre-wrap break-words leading-relaxed">{{ message.content }}</p>
              </div>
            </div>

            <!-- User message -->
            <div v-else class="max-w-[85%]">
              <div class="bg-accent rounded-2xl px-4 py-2 text-sm text-on-accent">
                <p class="whitespace-pre-wrap break-words leading-relaxed">{{ message.content }}</p>
              </div>
            </div>
          </div>

          <!-- Typing indicator -->
          <div v-if="chatStore.chatLoading" class="flex gap-2 justify-start">
            <div class="w-6 h-6" /><!-- Spacer for alignment -->
            <div class="bg-surface-2 rounded-2xl px-4 py-3 flex gap-1.5">
              <div class="w-2 h-2 bg-muted rounded-full animate-pulse" />
              <div class="w-2 h-2 bg-muted rounded-full animate-pulse" style="animation-delay: 0.2s" />
              <div class="w-2 h-2 bg-muted rounded-full animate-pulse" style="animation-delay: 0.4s" />
            </div>
          </div>

          <!-- Suggested questions (shown before first user message) -->
          <div
            v-if="chatStore.showSuggestedQuestions && chatStore.suggestedQuestions.length > 0"
            class="space-y-2"
          >
            <p class="text-xs text-muted font-medium">Try asking:</p>
            <div class="space-y-2">
              <button
                v-for="(question, index) in chatStore.suggestedQuestions.slice(0, 4)"
                :key="`suggested-${index}`"
                class="block w-full text-left text-sm px-4 py-3 rounded-xl bg-surface-2 text-fg border border-line hover-button-outlined focus-ring transition-all"
                style="min-height: 44px;"
                @click="handleSuggestedClick(question)"
              >
                {{ question }}
              </button>
            </div>
          </div>
        </div>

        <!-- Error display -->
        <div v-if="chatStore.chatError" class="px-4 pb-3 flex-shrink-0">
          <div class="bg-danger/10 border border-danger rounded-xl px-4 py-3 text-sm text-danger space-y-2">
            <p class="leading-relaxed">{{ chatStore.chatError }}</p>
            <div class="flex gap-2">
              <button
                v-if="chatStore.chatError.includes('contact me')"
                class="text-xs px-3 py-1.5 rounded-lg bg-danger text-white hover-button-primary focus-ring"
                @click="handleContactClick"
              >
                Contact me
              </button>
              <button
                v-if="chatStore.chatError.includes('Connection problem')"
                class="text-xs px-3 py-1.5 rounded-lg border border-danger text-danger hover-button-outlined focus-ring"
                @click="handleRetry"
              >
                Try again
              </button>
            </div>
          </div>
        </div>

        <!-- Input area -->
        <div class="border-t border-line px-4 py-3 space-y-2 flex-shrink-0">
          <!-- Textarea (auto-growing, 1-4 lines) -->
          <div class="relative">
            <textarea
              ref="textareaRef"
              v-model="inputText"
              :disabled="chatStore.chatLoading"
              :aria-invalid="!inputValid"
              placeholder="Type your question here..."
              class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-fg placeholder:text-muted/60 focus-ring disabled:opacity-50 disabled:cursor-not-allowed resize-none transition-all"
              style="min-height: 48px; max-height: 96px;"
              rows="1"
              @keydown="handleKeydown"
            />
            
            <!-- Character counter (only shows when > 400 chars) -->
            <div
              v-if="showCharCount"
              class="absolute bottom-2 right-3 text-xs font-medium"
              :class="inputValid ? 'text-muted' : 'text-danger'"
            >
              {{ charCount }}/500
            </div>
          </div>

          <!-- Send button -->
          <button
            class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-accent text-on-accent font-medium text-sm hover-button-primary focus-ring disabled:opacity-50 disabled:cursor-not-allowed transition-all"
            style="min-height: 44px;"
            :disabled="!canSend"
            @click="handleSend"
          >
            <span v-if="chatStore.chatLoading">Sending...</span>
            <template v-else>
              <span>Send</span>
              <Icon icon="lucide:arrow-right" class="w-4 h-4" />
            </template>
          </button>

          <!-- Footer disclaimer -->
          <div class="flex items-center justify-between gap-2 text-xs text-muted">
            <p>I'm an AI, so I can make mistakes.</p>
            <button
              class="text-accent hover-link focus-ring underline"
              @click="handleContactClick"
            >
              Contact me
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
/* Themed scrollbar for message list */
.chat-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: var(--color-line) transparent;
  color-scheme: light dark;
}

.chat-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.chat-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.chat-scrollbar::-webkit-scrollbar-thumb {
  background-color: var(--color-line);
  border-radius: 3px;
}

.dark .chat-scrollbar {
  color-scheme: dark;
}

/* Speech bubble transitions */
.speech-bubble-enter-active {
  transition: opacity 0.3s ease-out, transform 0.3s ease-out;
}

.speech-bubble-leave-active {
  transition: opacity 0.2s ease-in, transform 0.2s ease-in;
}

.speech-bubble-enter-from,
.speech-bubble-leave-to {
  opacity: 0;
  transform: translateY(0.5rem) scale(0.95);
}

/* Backdrop transitions */
.chat-backdrop-enter-active,
.chat-backdrop-leave-active {
  transition: opacity 0.3s ease;
}

.chat-backdrop-enter-from,
.chat-backdrop-leave-to {
  opacity: 0;
}

/* Panel transitions */
.chat-panel-enter-active {
  transition: opacity 0.3s ease-out, transform 0.3s ease-out;
}

.chat-panel-leave-active {
  transition: opacity 0.2s ease-in, transform 0.2s ease-in;
}

/* Mobile: slide up from bottom */
.chat-panel-enter-from,
.chat-panel-leave-to {
  opacity: 0;
  transform: translateY(2rem);
}

/* Desktop: slide from bottom-right */
@media (min-width: 640px) {
  .chat-panel {
    bottom: calc(max(1rem, env(safe-area-inset-bottom, 1rem)) + 64px) !important;
    height: min(36rem, calc(100dvh - 7rem)) !important;
  }

  .chat-panel-enter-from,
  .chat-panel-leave-to {
    transform: translateY(1rem);
  }
}

/* Respect prefers-reduced-motion */
@media (prefers-reduced-motion: reduce) {
  .speech-bubble-enter-active,
  .speech-bubble-leave-active,
  .chat-backdrop-enter-active,
  .chat-backdrop-leave-active,
  .chat-panel-enter-active,
  .chat-panel-leave-active {
    transition: opacity 0.01ms ease;
  }

  .speech-bubble-enter-from,
  .speech-bubble-leave-to,
  .chat-panel-enter-from,
  .chat-panel-leave-to {
    opacity: 0;
    transform: none;
  }

  .animate-pulse {
    animation: none !important;
  }
}

/* Extra small breakpoint for "Ask me" vs "Ask me anything" */
@media (min-width: 400px) {
  .xs\:inline {
    display: inline;
  }
  .xs\:hidden {
    display: none;
  }
}
</style>
