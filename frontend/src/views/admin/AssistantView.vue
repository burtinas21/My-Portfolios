<!--
  AssistantView.vue — Admin AI Assistant Settings
  ────────────────────────────────────────────────
  Configure the portfolio AI assistant:
  - Enable/disable toggle
  - Greeting message
  - Extra knowledge (rates, availability, FAQs)
  - Suggested questions (up to 6)
  - Test chat box (uses real /chat endpoint)
  - Usage statistics (today, 7 days, 30 days)
-->

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { getAdminAssistant, updateAdminAssistant, getAdminAssistantUsage } from '@/api/admin/assistant.js'
import { sendChat } from '@/api/assistant.js'
import { useToast } from '@/composables/useToast.js'
import { useDocumentTitle } from '@/composables/useDocumentTitle.js'
import { usePortfolioStore } from '@/stores/portfolio.js'
import BaseButton from '@/components/ui/BaseButton.vue'

useDocumentTitle('AI Assistant')

const toast = useToast()
const portfolioStore = usePortfolioStore()

/* ── Form State ────────────────────────────────────────────────── */

const form = reactive({
  enabled: false,
  greeting: '',
  extra_knowledge: '',
  suggested_questions: [],
})

const errors = reactive({
  enabled: '',
  greeting: '',
  extra_knowledge: '',
  suggested_questions: '',
})

const isLoading = ref(true)
const isSaving = ref(false)
const loadError = ref('')

/* ── Usage Stats State ────────────────────────────────────────── */

const usage = ref({
  today: { requests: 0, input_tokens: 0, output_tokens: 0 },
  last_7_days: { requests: 0, input_tokens: 0, output_tokens: 0 },
  last_30_days: { requests: 0, input_tokens: 0, output_tokens: 0 },
})
const usageLoading = ref(false)
const usageError = ref('')

/* ── Test Chat State ──────────────────────────────────────────── */

const testMessages = ref([])
const testInput = ref('')
const testLoading = ref(false)
const testError = ref(null)

/* ── Computed ──────────────────────────────────────────────────── */

const extraKnowledgeChars = computed(() => form.extra_knowledge.length)
const suggestedQuestionsCount = computed(() => form.suggested_questions.length)

const testInputValid = computed(() => {
  return testInput.value.trim().length > 0 && testInput.value.length <= 500
})

const canSendTest = computed(() => {
  return testInputValid.value && !testLoading.value && form.enabled
})

/* ── Lifecycle ─────────────────────────────────────────────────── */

onMounted(async () => {
  await Promise.all([loadSettings(), loadUsage()])
})

/* ── Methods ───────────────────────────────────────────────────── */

async function loadSettings() {
  isLoading.value = true
  loadError.value = ''
  try {
    const data = await getAdminAssistant()
    form.enabled = data.enabled ?? false
    form.greeting = data.greeting ?? ''
    form.extra_knowledge = data.extra_knowledge ?? ''
    form.suggested_questions = data.suggested_questions ?? []
  } catch (err) {
    loadError.value = err.message || 'Failed to load settings.'
  } finally {
    isLoading.value = false
  }
}

async function loadUsage() {
  usageLoading.value = true
  usageError.value = ''
  try {
    usage.value = await getAdminAssistantUsage()
  } catch (err) {
    usageError.value = err.message || 'Failed to load usage stats.'
  } finally {
    usageLoading.value = false
  }
}

function clearErrors() {
  Object.keys(errors).forEach((k) => (errors[k] = ''))
}

async function handleSave() {
  clearErrors()
  isSaving.value = true

  try {
    const payload = {
      enabled: form.enabled,
      greeting: form.greeting.trim(),
      extra_knowledge: form.extra_knowledge.trim() || null,
      suggested_questions: form.suggested_questions
        .map((q) => q.trim())
        .filter(Boolean)
        .slice(0, 6), // Max 6
    }

    await updateAdminAssistant(payload)
    toast.success('Settings saved successfully.')
    await portfolioStore.refresh()

    // Reload usage stats to reflect new state
    await loadUsage()
  } catch (err) {
    if (err.fieldErrors) {
      Object.entries(err.fieldErrors).forEach(([field, msgs]) => {
        if (field in errors) errors[field] = msgs[0]
      })
    } else {
      toast.error(err.message || 'Failed to save settings.')
    }
  } finally {
    isSaving.value = false
  }
}

/* ── Suggested Questions List Editor ──────────────────────────── */

function addQuestion() {
  if (form.suggested_questions.length < 6) {
    form.suggested_questions.push('')
  }
}

function removeQuestion(index) {
  form.suggested_questions.splice(index, 1)
}

/* ── Test Chat Box ────────────────────────────────────────────── */

async function handleTestSend() {
  if (!canSendTest.value) return

  const userMessage = testInput.value.trim()
  testInput.value = ''

  // Add user message to local test history
  testMessages.value.push({
    role: 'user',
    content: userMessage,
  })

  testLoading.value = true
  testError.value = null

  try {
    // Build the message array to send (max 10)
    const messagesToSend = testMessages.value.slice(-10)
    const response = await sendChat(messagesToSend)

    // Add assistant reply
    testMessages.value.push({
      role: 'assistant',
      content: response.reply,
    })
  } catch (err) {
    if (err.status === 429) {
      testError.value = 'Too many questions. Please wait a minute.'
    } else if (err.status === 503) {
      testError.value = err.message || 'Assistant is unavailable.'
    } else {
      testError.value = err.message || 'Something went wrong.'
    }

    // Remove the user message so they can retry
    testMessages.value.pop()
  } finally {
    testLoading.value = false
  }
}

function handleTestKeydown(event) {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    if (canSendTest.value) {
      handleTestSend()
    }
  }
}

function clearTestChat() {
  testMessages.value = []
  testError.value = null
}
</script>

<template>
  <div class="max-w-4xl space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-fg">AI Assistant</h2>
        <p class="text-sm text-muted mt-1">Configure the "Ask about me" chat widget on your portfolio.</p>
      </div>
      <BaseButton variant="primary" size="md" :loading="isSaving" @click="handleSave">
        Save Changes
      </BaseButton>
    </div>

    <!-- Load error -->
    <div v-if="loadError" class="p-4 rounded-card bg-danger/10 border border-danger/30 text-danger text-sm">
      {{ loadError }}
    </div>

    <!-- Loading skeleton -->
    <div v-else-if="isLoading" class="space-y-5">
      <div v-for="i in 4" :key="i" class="h-32 skeleton rounded-card" />
    </div>

    <!-- Form -->
    <div v-else class="space-y-6">
      <!-- Enable Toggle -->
      <div class="bg-surface border border-line rounded-card p-6 shadow-card">
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <h3 class="text-sm font-semibold text-fg">Enable Assistant</h3>
            <p class="text-xs text-muted mt-1">
              When enabled, visitors will see a floating chat button on your portfolio.
            </p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              v-model="form.enabled"
              type="checkbox"
              class="sr-only peer"
            />
            <div class="w-11 h-6 bg-line rounded-full peer peer-checked:bg-accent peer-focus:ring-2 peer-focus:ring-accent transition-colors">
              <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full transition-transform peer-checked:translate-x-5" />
            </div>
          </label>
        </div>
        <p v-if="errors.enabled" class="text-xs text-danger mt-2">{{ errors.enabled }}</p>
      </div>

      <!-- Greeting -->
      <div class="bg-surface border border-line rounded-card p-6 shadow-card space-y-4">
        <h3 class="text-sm font-semibold text-fg">Greeting Message</h3>
        <div class="space-y-1.5">
          <label for="greeting" class="block text-xs font-medium text-muted">
            This is the first message visitors see when they open the chat.
          </label>
          <input
            id="greeting"
            v-model="form.greeting"
            type="text"
            maxlength="200"
            placeholder="Hello! Ask me anything about my work, skills, or experience."
            class="input-field w-full"
            :class="errors.greeting ? 'border-danger' : ''"
          />
          <div class="flex justify-between">
            <p v-if="errors.greeting" class="text-xs text-danger">{{ errors.greeting }}</p>
            <p class="text-xs text-muted ml-auto">{{ form.greeting.length }}/200</p>
          </div>
        </div>
      </div>

      <!-- Extra Knowledge -->
      <div class="bg-surface border border-line rounded-card p-6 shadow-card space-y-4">
        <h3 class="text-sm font-semibold text-fg">Extra Knowledge</h3>
        <div class="space-y-1.5">
          <label for="extra-knowledge" class="block text-xs font-medium text-muted">
            Additional information not in your portfolio database (rates, availability, languages, FAQs).
            Keep it short and factual.
          </label>
          <textarea
            id="extra-knowledge"
            v-model="form.extra_knowledge"
            rows="6"
            maxlength="3000"
            placeholder="I charge $100/hour for consulting.&#10;I'm available for full-time roles starting next month.&#10;I speak English, Spanish, and French.&#10;I prefer remote work but open to hybrid in Vilnius."
            class="input-field w-full resize-none"
            :class="errors.extra_knowledge ? 'border-danger' : ''"
          />
          <div class="flex justify-between">
            <p v-if="errors.extra_knowledge" class="text-xs text-danger">{{ errors.extra_knowledge }}</p>
            <p class="text-xs text-muted ml-auto">{{ extraKnowledgeChars }}/3000</p>
          </div>
        </div>
      </div>

      <!-- Suggested Questions -->
      <div class="bg-surface border border-line rounded-card p-6 shadow-card space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-sm font-semibold text-fg">Suggested Questions</h3>
            <p class="text-xs text-muted mt-1">Up to 6 starter questions shown before the first user message.</p>
          </div>
          <BaseButton
            variant="secondary"
            size="sm"
            :disabled="suggestedQuestionsCount >= 6"
            @click="addQuestion"
          >
            <svg class="w-3.5 h-3.5 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add
          </BaseButton>
        </div>

        <div v-if="form.suggested_questions.length === 0" class="text-center py-6 text-muted text-sm">
          No suggested questions yet. Click "Add" to create one.
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="(question, index) in form.suggested_questions"
            :key="index"
            class="flex items-center gap-2"
          >
            <span class="text-xs text-muted w-6 shrink-0">{{ index + 1 }}.</span>
            <input
              v-model="form.suggested_questions[index]"
              type="text"
              maxlength="100"
              placeholder="What are your main skills?"
              class="input-field flex-1"
            />
            <button
              type="button"
              class="text-danger hover:text-danger/80 transition-colors p-2"
              aria-label="Remove question"
              @click="removeQuestion(index)"
            >
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
          </div>
        </div>

        <p v-if="errors.suggested_questions" class="text-xs text-danger">{{ errors.suggested_questions }}</p>
      </div>

      <!-- Test Chat Box -->
      <div class="bg-surface border border-line rounded-card p-6 shadow-card space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-sm font-semibold text-fg">Test It</h3>
            <p class="text-xs text-muted mt-1">Try questions here. Uses the real /chat endpoint.</p>
          </div>
          <BaseButton
            v-if="testMessages.length > 0"
            variant="ghost"
            size="sm"
            @click="clearTestChat"
          >
            Clear
          </BaseButton>
        </div>

        <div v-if="!form.enabled" class="text-center py-8 text-muted text-sm">
          Enable the assistant above to test it.
        </div>

        <div v-else class="space-y-3">
          <!-- Message list -->
          <div class="max-h-64 overflow-y-auto space-y-2 px-2 py-2 border border-line rounded-control bg-surface-2">
            <div v-if="testMessages.length === 0" class="text-center py-8 text-muted text-xs">
              No messages yet. Type a question below.
            </div>

            <div
              v-for="(msg, idx) in testMessages"
              :key="`test-${idx}`"
              :class="[
                'flex gap-2',
                msg.role === 'user' ? 'justify-end' : 'justify-start',
              ]"
            >
              <div
                :class="[
                  'max-w-xs rounded-lg px-3 py-2 text-xs',
                  msg.role === 'user'
                    ? 'bg-accent text-on-accent'
                    : 'bg-surface border border-line text-fg',
                ]"
              >
                <p class="whitespace-pre-wrap break-words">{{ msg.content }}</p>
              </div>
            </div>

            <!-- Typing indicator -->
            <div v-if="testLoading" class="flex gap-2 justify-start">
              <div class="bg-surface border border-line rounded-lg px-3 py-2 flex gap-1.5">
                <div class="h-1.5 w-1.5 bg-muted rounded-full animate-pulse" />
                <div class="h-1.5 w-1.5 bg-muted rounded-full animate-pulse" style="animation-delay: 0.2s" />
                <div class="h-1.5 w-1.5 bg-muted rounded-full animate-pulse" style="animation-delay: 0.4s" />
              </div>
            </div>

            <!-- Error -->
            <div v-if="testError" class="bg-danger/10 border border-danger rounded-lg px-3 py-2 text-xs text-danger">
              {{ testError }}
            </div>
          </div>

          <!-- Input -->
          <div class="flex items-end gap-2">
            <textarea
              v-model="testInput"
              :disabled="testLoading"
              placeholder="Ask a test question..."
              class="input-field flex-1 resize-none"
              rows="2"
              maxlength="500"
              @keydown="handleTestKeydown"
            />
            <BaseButton
              variant="primary"
              size="md"
              :disabled="!canSendTest"
              :loading="testLoading"
              @click="handleTestSend"
            >
              Send
            </BaseButton>
          </div>
          <p class="text-xs text-muted">Enter to send, Shift+Enter for new line.</p>
        </div>
      </div>

      <!-- Usage Stats -->
      <div class="bg-surface border border-line rounded-card p-6 shadow-card space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-sm font-semibold text-fg">Usage Statistics</h3>
            <p class="text-xs text-muted mt-1">
              Token usage across different periods. Check
              <a
                href="https://console.anthropic.com"
                target="_blank"
                rel="noopener noreferrer"
                class="underline hover:text-accent transition-colors"
              >Anthropic Console</a>
              for billing.
            </p>
          </div>
          <BaseButton variant="ghost" size="sm" :loading="usageLoading" @click="loadUsage">
            Refresh
          </BaseButton>
        </div>

        <div v-if="usageError" class="p-3 rounded-control bg-danger/10 border border-danger/30 text-danger text-xs">
          {{ usageError }}
        </div>

        <div v-else-if="usageLoading" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div v-for="i in 3" :key="i" class="h-24 skeleton rounded-control" />
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <!-- Today -->
          <div class="p-4 rounded-control bg-surface-2 border border-line">
            <p class="text-xs font-semibold text-muted uppercase tracking-wide">Today</p>
            <p class="text-2xl font-bold text-fg mt-1">{{ usage.today.requests }}</p>
            <p class="text-xs text-muted mt-1">requests</p>
            <div class="mt-2 flex items-center gap-3 text-xs text-muted">
              <span>↑ {{ usage.today.input_tokens.toLocaleString() }}</span>
              <span>↓ {{ usage.today.output_tokens.toLocaleString() }}</span>
            </div>
          </div>

          <!-- Last 7 Days -->
          <div class="p-4 rounded-control bg-surface-2 border border-line">
            <p class="text-xs font-semibold text-muted uppercase tracking-wide">Last 7 Days</p>
            <p class="text-2xl font-bold text-fg mt-1">{{ usage.last_7_days.requests }}</p>
            <p class="text-xs text-muted mt-1">requests</p>
            <div class="mt-2 flex items-center gap-3 text-xs text-muted">
              <span>↑ {{ usage.last_7_days.input_tokens.toLocaleString() }}</span>
              <span>↓ {{ usage.last_7_days.output_tokens.toLocaleString() }}</span>
            </div>
          </div>

          <!-- Last 30 Days -->
          <div class="p-4 rounded-control bg-surface-2 border border-line">
            <p class="text-xs font-semibold text-muted uppercase tracking-wide">Last 30 Days</p>
            <p class="text-2xl font-bold text-fg mt-1">{{ usage.last_30_days.requests }}</p>
            <p class="text-xs text-muted mt-1">requests</p>
            <div class="mt-2 flex items-center gap-3 text-xs text-muted">
              <span>↑ {{ usage.last_30_days.input_tokens.toLocaleString() }}</span>
              <span>↓ {{ usage.last_30_days.output_tokens.toLocaleString() }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Save button (bottom) -->
      <div class="flex justify-end">
        <BaseButton variant="primary" size="md" :loading="isSaving" @click="handleSave">
          Save Changes
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<style scoped>
@reference "@/assets/main.css";

.input-field {
  @apply px-3.5 py-2.5 rounded-control border border-line bg-surface text-sm text-fg
         placeholder:text-muted transition-colors outline-none
         focus:ring-2 focus:ring-accent focus:border-accent
         disabled:opacity-50 disabled:cursor-not-allowed;
}
</style>
