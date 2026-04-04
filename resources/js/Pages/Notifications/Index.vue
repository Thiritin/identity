<script setup>
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'
import { Button } from '@/Components/ui/button'

const props = defineProps({
  notifications: Object,
})

function groupByTime(list) {
  const now = Date.now()
  const DAY = 86400000
  const groups = {
    Today: [],
    Yesterday: [],
    'This week': [],
    'Last week': [],
    Older: [],
  }
  for (const n of list) {
    const age = now - new Date(n.created_at).getTime()
    if (age < DAY) groups.Today.push(n)
    else if (age < 2 * DAY) groups.Yesterday.push(n)
    else if (age < 7 * DAY) groups['This week'].push(n)
    else if (age < 14 * DAY) groups['Last week'].push(n)
    else groups.Older.push(n)
  }
  return groups
}

async function markAllRead() {
  await axios.post('/notifications/read-all')
  router.reload()
}

async function clearAll() {
  if (!confirm('Clear all notifications?')) return
  await axios.delete('/notifications')
  router.reload()
}

async function clickItem(n) {
  await axios.post(`/notifications/${n.id}/read`)
  if (n.cta_url) {
    window.location.href = n.cta_url
  } else {
    router.reload()
  }
}
</script>

<template>
  <Head title="Notifications" />
  <div class="grid md:grid-cols-3 gap-6 md:gap-10">
    <div>
      <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Notifications</h3>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
        All notifications from apps you use. Kept for 90 days.
      </p>
    </div>
    <div class="md:col-span-2 space-y-6">
      <div class="flex justify-end gap-2">
        <Button variant="outline" size="sm" @click="markAllRead">Mark all as read</Button>
        <Button variant="outline" size="sm" @click="clearAll">Clear all</Button>
      </div>

      <template v-for="(items, bucket) in groupByTime(notifications.data)" :key="bucket">
        <section v-if="items.length > 0">
          <h2 class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-2">{{ bucket }}</h2>
          <div
            v-for="n in items"
            :key="n.id"
            class="rounded-md border dark:border-gray-700 p-3 mb-2 cursor-pointer hover:bg-black/5 dark:hover:bg-white/5"
            :class="{ 'bg-primary-50 dark:bg-primary-900/20 font-semibold': !n.read_at }"
            @click="clickItem(n)"
          >
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ n.app?.name }}</div>
            <div class="text-sm">{{ n.subject }}</div>
            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">{{ n.body }}</div>
            <div v-if="n.cta_label" class="mt-1 text-xs text-primary-600 dark:text-primary-400">
              {{ n.cta_label }} →
            </div>
          </div>
        </section>
      </template>
    </div>
  </div>
</template>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'
export default { layout: AccountLayout }
</script>
