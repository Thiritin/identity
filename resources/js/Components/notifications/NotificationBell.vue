<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'
import { Bell } from 'lucide-vue-next'

const unreadCount = ref(0)
const recent = ref([])
const open = ref(false)

async function load() {
  try {
    const { data } = await axios.get('/notifications/recent')
    unreadCount.value = data.unread_count ?? 0
    recent.value = data.recent ?? []
  } catch (e) {
    // silently ignore; bell is decorative if the fetch fails
  }
}

onMounted(load)
</script>

<template>
  <div class="relative">
    <button
      type="button"
      @click="open = !open"
      class="relative inline-flex items-center justify-center p-2 rounded-md hover:bg-black/10 text-white"
      :aria-label="`Notifications${unreadCount > 0 ? ` (${unreadCount} unread)` : ''}`"
    >
      <Bell class="h-5 w-5" />
      <span
        v-if="unreadCount > 0"
        class="absolute top-1 right-1 inline-flex h-2 w-2 rounded-full bg-red-500"
        aria-hidden="true"
      ></span>
    </button>
    <div
      v-if="open"
      class="absolute right-0 mt-2 w-80 rounded-md border bg-white dark:bg-gray-900 dark:border-gray-700 shadow-lg z-50"
    >
      <div class="px-3 py-2 border-b dark:border-gray-700 text-sm font-semibold">Notifications</div>
      <div v-if="recent.length === 0" class="px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
        No notifications
      </div>
      <div
        v-for="n in recent"
        :key="n.id"
        class="px-3 py-2 border-b last:border-0 dark:border-gray-700 text-sm"
        :class="{ 'font-semibold': !n.read_at }"
      >
        <div class="text-xs text-gray-500 dark:text-gray-400">{{ n.app?.name }}</div>
        <div class="truncate">{{ n.subject }}</div>
      </div>
      <Link href="/notifications" class="block text-center text-sm px-3 py-2 hover:bg-black/5 dark:hover:bg-white/5">
        View all
      </Link>
    </div>
  </div>
</template>
