/**
 * stores/chat.js — Chat Widget Store
 * ────────────────────────────────────
 * Manages the state of the floating AI assistant chat widget.
 *
 * Key responsibilities:
 * 1. Fetch assistant config (greeting, suggested questions, enabled status)
 * 2. Manage conversation history (persist in sessionStorage, cap at 10 messages)
 * 3. Send user messages and receive AI replies
 * 4. Handle loading, errors, and throttle/disabled states
 * 5. Manage widget open/close state
 *
 * Design decisions:
 * - Persistence: sessionStorage only (cleared when tab closes)
 * - Message cap: 10 messages sent to server (local copy can be longer for context)
 * - Auto-disable: if GET /assistant fails, widget is hidden
 * - No auto-open: widget starts closed, user clicks button to open
 *
 * Used by: ChatWidget.vue
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { sendChat } from '@/api/assistant.js'
import { usePortfolioStore } from '@/stores/portfolio.js'

const STORAGE_KEY = 'chat_messages'
const MAX_MESSAGES_TO_SEND = 10

export const useChatStore = defineStore('chat', () => {
  const portfolioStore = usePortfolioStore()

  /* ── Configuration State ───────────────────────────────────── */

  const enabled = computed(() => portfolioStore.assistant.enabled)
  const greeting = computed(() => portfolioStore.assistant.greeting)
  const suggestedQuestions = computed(() => portfolioStore.assistant.suggested_questions)

  /** Loading while fetching assistant config */
  const configLoading = ref(false)

  /** Error message if config fetch fails */
  const configError = ref(null)

  /* ── Conversation State ────────────────────────────────────── */

  /** Array of messages: { role: "user"|"assistant", content: string } */
  const messages = ref([])

  /** Loading while waiting for AI response */
  const chatLoading = ref(false)

  /** Last error from /chat endpoint */
  const chatError = ref(null)

  /* ── UI State ──────────────────────────────────────────────── */

  /** Whether the chat panel is open */
  const isOpen = ref(false)

  /* ── Computed ──────────────────────────────────────────────── */

  /**
   * True if widget should be shown (enabled && config loaded successfully)
   */
  const isVisible = computed(() => enabled.value && !configError.value)

  /**
   * Messages to send to the server: max MAX_MESSAGES_TO_SEND, last must be user
   */
  const messagesToSend = computed(() => {
    const recent = messages.value.slice(-MAX_MESSAGES_TO_SEND)
    // If the last message isn't from user, server will reject anyway
    return recent
  })

  /**
   * True if suggested questions haven't been replaced by user input
   */
  const showSuggestedQuestions = computed(() => {
    // Hide suggestions after the first user message
    return messages.value.length === 0
  })

  /* ── Persistence ───────────────────────────────────────────── */

  /**
   * Load messages from sessionStorage (called on mount)
   */
  function loadMessages() {
    try {
      const stored = sessionStorage.getItem(STORAGE_KEY)
      if (stored) {
        messages.value = JSON.parse(stored)
      }
    } catch (err) {
      console.error('Failed to load chat messages:', err)
      messages.value = []
    }
  }

  /**
   * Save messages to sessionStorage whenever they change
   */
  function saveMessages() {
    try {
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify(messages.value))
    } catch (err) {
      console.error('Failed to save chat messages:', err)
    }
  }

  /* ── Actions ───────────────────────────────────────────────── */

  /**
   * Fetch assistant config (greeting, suggested questions, enabled).
   * Called on app init, before showing the widget.
   */
  async function fetchConfig() {
    configLoading.value = true
    configError.value = null

    try {
      const loaded = await portfolioStore.fetchAll()
      if (!loaded) {
        throw new Error(portfolioStore.assistantError || 'Failed to load assistant')
      }
    } catch (err) {
      configError.value = err.message || 'Failed to load assistant'
    } finally {
      configLoading.value = false
    }
  }

  /**
   * Send a message and receive a reply from the assistant.
   *
   * @param {string} userMessage — The user's message (will be trimmed)
   */
  async function sendMessage(userMessage) {
    const trimmed = userMessage.trim()
    if (!trimmed) return

    // Add user message to local history
    messages.value.push({
      role: 'user',
      content: trimmed,
    })
    saveMessages()

    // Start loading
    chatLoading.value = true
    chatError.value = null

    try {
      // Send the last N messages to the server (cap at 10)
      const response = await sendChat(messagesToSend.value)

      // Add assistant reply to history
      messages.value.push({
        role: 'assistant',
        content: response.reply,
      })
      saveMessages()
    } catch (err) {
      // Friendly error messages for non-technical visitors
      if (err.status === 429) {
        chatError.value = 'Lots of questions right now. Please try again in a minute.'
      } else if (err.status === 503 || err.status === 502) {
        chatError.value = 'I can\'t answer right now. Please contact me using the form.'
      } else if (err.message && err.message.toLowerCase().includes('network')) {
        chatError.value = 'Connection problem. Please check your internet and try again.'
      } else if (err.status === 422) {
        // Validation error — shouldn't happen if we validate locally first
        chatError.value = 'Your message is too long. Please keep it under 500 characters.'
      } else {
        chatError.value = 'Something went wrong. Please try again or use the contact form.'
      }

      // Remove the user message if the request failed, so they can retry
      messages.value.pop()
      saveMessages()
    } finally {
      chatLoading.value = false
    }
  }

  /**
   * Handle a suggested question: send it as a user message
   *
   * @param {string} question — The suggested question
   */
  async function askSuggestedQuestion(question) {
    await sendMessage(question)
  }

  /**
   * Open the chat panel
   */
  function open() {
    isOpen.value = true
  }

  /**
   * Close the chat panel
   */
  function close() {
    isOpen.value = false
  }

  /**
   * Toggle the chat panel open/closed
   */
  function toggle() {
    isOpen.value = !isOpen.value
  }

  /**
   * Clear the conversation history and close the panel
   */
  function reset() {
    messages.value = []
    chatError.value = null
    isOpen.value = false
    saveMessages()
  }

  return {
    /* Configuration */
    enabled,
    greeting,
    suggestedQuestions,
    configLoading,
    configError,

    /* Conversation */
    messages,
    chatLoading,
    chatError,
    messagesToSend,

    /* UI */
    isOpen,

    /* Computed */
    isVisible,
    showSuggestedQuestions,

    /* Actions */
    fetchConfig,
    loadMessages,
    saveMessages,
    sendMessage,
    askSuggestedQuestion,
    open,
    close,
    toggle,
    reset,
  }
})
