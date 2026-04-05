<template>
    <div>
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Recent deliveries</h3>

        <div v-if="loading && deliveries.length === 0" class="text-sm text-gray-500 dark:text-gray-400 py-4">
            Loading…
        </div>

        <div v-else-if="!loading && deliveries.length === 0" class="text-sm text-gray-500 dark:text-gray-400 py-4">
            No deliveries yet.
        </div>

        <div v-else class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                        <th class="text-left px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">When</th>
                        <th class="text-left px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">Event</th>
                        <th class="text-left px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="text-left px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">HTTP</th>
                        <th class="text-left px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">Attempts</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="delivery in deliveries" :key="delivery.id">
                        <tr
                            class="border-b border-gray-100 dark:border-gray-800 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors"
                            @click="toggleExpand(delivery.id)"
                        >
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ formatDate(delivery.created_at) }}
                            </td>
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300 font-mono text-xs">
                                {{ delivery.event }}
                            </td>
                            <td class="px-4 py-2">
                                <span :class="statusClass(delivery.status)" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium">
                                    {{ delivery.status }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                                {{ delivery.response_code ?? '—' }}
                            </td>
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                                {{ delivery.attempts }}
                            </td>
                        </tr>
                        <tr v-if="expandedId === delivery.id" class="bg-gray-50 dark:bg-gray-800/20">
                            <td colspan="5" class="px-4 py-4">
                                <div class="space-y-3">
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Payload</p>
                                        <pre class="text-xs font-mono bg-gray-100 dark:bg-gray-900 rounded p-3 overflow-auto max-h-48 text-gray-800 dark:text-gray-200">{{ JSON.stringify(delivery.payload, null, 2) }}</pre>
                                    </div>

                                    <div v-if="delivery.signature">
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Signature</p>
                                        <span class="text-xs font-mono text-gray-700 dark:text-gray-300 break-all">{{ delivery.signature }}</span>
                                    </div>

                                    <div v-if="delivery.response_code">
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Response code</p>
                                        <span class="text-xs text-gray-700 dark:text-gray-300">{{ delivery.response_code }}</span>
                                    </div>

                                    <div v-if="delivery.response_body">
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Response body</p>
                                        <pre class="text-xs font-mono bg-gray-100 dark:bg-gray-900 rounded p-3 overflow-auto max-h-32 text-gray-800 dark:text-gray-200">{{ delivery.response_body }}</pre>
                                    </div>

                                    <div v-if="delivery.error">
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Error</p>
                                        <span class="text-xs text-red-600 dark:text-red-400">{{ delivery.error }}</span>
                                    </div>

                                    <div v-if="delivery.next_retry_at">
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Next retry</p>
                                        <span class="text-xs text-gray-700 dark:text-gray-300">{{ formatDate(delivery.next_retry_at) }}</span>
                                    </div>

                                    <div>
                                        <button
                                            type="button"
                                            class="text-xs px-3 py-1.5 rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
                                            :disabled="redelivering === delivery.id"
                                            @click.stop="redeliver(delivery)"
                                        >
                                            {{ redelivering === delivery.id ? 'Redelivering…' : 'Redeliver' }}
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div v-if="nextPageUrl" class="mt-3">
            <button
                type="button"
                class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 disabled:opacity-50"
                :disabled="loadingMore"
                @click="loadMore"
            >
                {{ loadingMore ? 'Loading…' : 'Load more' }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
    appId: {
        type: Number,
        required: true,
    },
})

const deliveries = ref([])
const loading = ref(false)
const loadingMore = ref(false)
const nextPageUrl = ref(null)
const expandedId = ref(null)
const redelivering = ref(null)

function csrf() {
    return {
        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    }
}

async function fetchDeliveries(url) {
    const res = await fetch(url, {
        headers: { 'Accept': 'application/json' },
    })
    return res.json()
}

async function load() {
    loading.value = true
    try {
        const data = await fetchDeliveries(route('developers.webhooks.deliveries', props.appId))
        deliveries.value = data.data ?? data
        nextPageUrl.value = data.next_page_url ?? null
    } finally {
        loading.value = false
    }
}

async function loadMore() {
    if (!nextPageUrl.value) return
    loadingMore.value = true
    try {
        const data = await fetchDeliveries(nextPageUrl.value)
        deliveries.value.push(...(data.data ?? data))
        nextPageUrl.value = data.next_page_url ?? null
    } finally {
        loadingMore.value = false
    }
}

function reload() {
    expandedId.value = null
    load()
}

function toggleExpand(id) {
    expandedId.value = expandedId.value === id ? null : id
}

async function redeliver(delivery) {
    redelivering.value = delivery.id
    try {
        await fetch(route('developers.webhooks.redeliver', [props.appId, delivery.id]), {
            method: 'POST',
            headers: csrf(),
        })
        reload()
    } finally {
        redelivering.value = null
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleString()
}

function statusClass(status) {
    switch (status) {
        case 'delivered':
            return 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
        case 'retrying':
            return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400'
        case 'failed':
            return 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400'
        default:
            return 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
    }
}

onMounted(load)

defineExpose({ reload })
</script>
