<!--
  MessagesView.vue — Admin Messages Inbox
  ─────────────────────────────────────────
  Displays incoming contact messages.
  - List all messages (newest first)
  - Mark as read / unread indicator
  - View full message content inline
  - Delete with confirmation
-->

<script setup>
import { ref, computed, onMounted } from 'vue'
import { getAdminMessages, markMessageRead, deleteAdminMessage } from '@/api/admin/messages.js'
import { useToast } from '@/composables/useToast.js'
import { useDocumentTitle } from '@/composables/useDocumentTitle.js'
import BaseButton from '@/components/ui/BaseButton.vue'
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue'

useDocumentTitle('Messages')

const toast = useToast()

const messages = ref([])
const isLoading = ref(true)
const loadError = ref('')
const expandedId = ref(null)

// Delete confirm
const confirmOpen = ref(false)
const isDeleting = ref(false)
const deleteTarget = ref(null)

const unreadCount = computed(() => messages.value.filter((m) => !m.read_at).length)

async function load() {
  isLoading.value = true
  loadError.value = ''
  try {
    messages.value = await getAdminMessages()
  } catch (err) {
    loadError.value = err.message || 'Failed to load messages.'
  } finally {
    isLoading.value = false
  }
}

onMounted(load)

async function toggleExpand(message) {
  if (expandedId.value === message.id) {
    expandedId.value = null
    return
  }
  expandedId.value = message.id

  // Mark as read if not already
  if (!message.read_at) {
    try {
      const updated = await markMessageRead(message.id)
      const idx = messages.value.findIndex((m) => m.id === message.id)
      if (idx !== -1) messages.value[idx] = updated
    } catch {
      // Non-critical failure — don't bother the user
    }
  }
}

function openDelete(message) {
  deleteTarget.value = message
  confirmOpen.value = true
}

async function handleDelete() {
  if (!deleteTarget.value) return
  isDeleting.value = true
  try {
    await deleteAdminMessage(deleteTarget.value.id)
    messages.value = messages.value.filter((m) => m.id !== deleteTarget.value.id)
    if (expandedId.value === deleteTarget.value.id) expandedId.value = null
    toast.success('Message deleted.')
    confirmOpen.value = false
  } catch (err) {
    toast.error(err.message || 'Delete failed.')
  } finally {
    isDeleting.value = false
  }
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(dateStr))
}
</script>

<template>
  <div class="max-w-3xl space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-fg">Messages</h2>
        <p class="text-sm text-muted mt-1">
          {{ unreadCount > 0 ? `${unreadCount} unread message${unreadCount > 1 ? 's' : ''}` : 'All caught up!' }}
        </p>
      </div>
    </div>

    <!-- Error -->
    <div v-if="loadError" class="p-4 rounded-card bg-danger/10 border border-danger/30 text-danger text-sm">{{ loadError }}</div>

    <!-- Loading -->
    <div v-else-if="isLoading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="h-20 skeleton rounded-card" />
    </div>

    <!-- Empty -->
    <div v-else-if="messages.length === 0" class="text-center py-16 text-muted">
      <svg class="w-12 h-12 mx-auto mb-4 opacity-30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <p class="text-lg font-medium">No messages yet</p>
      <p class="text-sm mt-1">Contact form messages will appear here.</p>
    </div>

    <!-- Message list -->
    <div v-else class="space-y-2">
      <div
        v-for="message in messages"
        :key="message.id"
        class="bg-surface border rounded-card shadow-card overflow-hidden transition-colors"
        :class="message.read_at ? 'border-line' : 'border-accent/40'"
      >
        <!-- Collapsed row (always visible) -->
        <div
          class="flex items-start gap-4 px-5 py-4 cursor-pointer hover-table-row focus-ring"
          tabindex="0"
          role="button"
          :aria-expanded="expandedId === message.id"
          @click="toggleExpand(message)"
          @keydown.enter="toggleExpand(message)"
          @keydown.space.prevent="toggleExpand(message)"
        >
          <!-- Unread indicator -->
          <div class="mt-1 shrink-0">
            <span
              class="block w-2.5 h-2.5 rounded-full transition-colors"
              :class="message.read_at ? 'bg-line' : 'bg-accent'"
              :title="message.read_at ? 'Read' : 'Unread'"
            />
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2 justify-between">
              <span class="font-semibold text-sm text-fg">{{ message.name }}</span>
              <span class="text-xs text-muted">{{ formatDate(message.created_at) }}</span>
            </div>
            <p class="text-xs text-accent truncate">{{ message.email }}</p>
            <p class="text-sm text-muted mt-0.5 line-clamp-1">{{ message.body }}</p>
          </div>

          <svg
            class="w-4 h-4 text-muted shrink-0 mt-0.5 transition-transform"
            :class="expandedId === message.id ? 'rotate-180' : ''"
            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
          >
            <path d="M6 9l6 6 6-6"/>
          </svg>
        </div>

        <!-- Expanded body -->
        <div v-if="expandedId === message.id" class="border-t border-line px-5 py-4 bg-surface-2 space-y-4">
          <div>
            <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-1">From</p>
            <p class="text-sm text-fg">{{ message.name }} &lt;<a :href="`mailto:${message.email}`" class="text-accent hover:underline">{{ message.email }}</a>&gt;</p>
          </div>
          <div>
            <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-1">Message</p>
            <p class="text-sm text-fg whitespace-pre-wrap leading-relaxed">{{ message.body }}</p>
          </div>
          <div class="flex items-center gap-3 pt-2">
            <a
              :href="`mailto:${message.email}?subject=Re: Your message`"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-control bg-accent text-on-accent text-xs font-medium hover-button-primary focus-ring"
            >
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
              Reply via Email
            </a>
            <BaseButton variant="ghost" size="sm" class="text-danger hover:text-danger text-xs" @click.stop="openDelete(message)">Delete</BaseButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirm -->
    <ConfirmDialog
      v-model:open="confirmOpen"
      title="Delete Message"
      :message="`Message from '${deleteTarget?.name}' will be permanently deleted.`"
      :loading="isDeleting"
      @confirm="handleDelete"
    />
  </div>
</template>
