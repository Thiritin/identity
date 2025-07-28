<template>
    <Head title="Security Activity"></Head>
    
    <div class="max-w-4xl mx-auto p-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Security Activity</h1>
            <p class="text-gray-600">
                Monitor recent security events and activities on your account.
            </p>
        </div>

        <!-- Activity Feed -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>
                <p class="text-gray-600 text-sm mt-1">
                    Your recent authentication and security events
                </p>
            </div>

            <div v-if="!events.length" class="p-6 text-center">
                <ShieldCheckIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 mb-2">No recent activity</h3>
                <p class="text-gray-600">
                    Security events will appear here when they occur.
                </p>
            </div>

            <div v-else class="divide-y divide-gray-200">
                <div
                    v-for="event in events"
                    :key="event.id"
                    class="p-6 flex items-start space-x-4"
                >
                    <div class="shrink-0">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center"
                             :class="getEventIconClass(event.event)">
                            <component :is="getEventIcon(event.event)" class="w-5 h-5" />
                        </div>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900">
                                {{ event.description }}
                            </p>
                            <time class="text-xs text-gray-500 shrink-0">
                                {{ formatTime(event.timestamp) }}
                            </time>
                        </div>
                        
                        <div class="mt-1 text-xs text-gray-500 space-y-1">
                            <p v-if="event.ip_address">
                                IP Address: {{ event.ip_address }}
                            </p>
                            <p v-if="event.user_agent && showUserAgent(event.user_agent)">
                                {{ formatUserAgent(event.user_agent) }}
                            </p>
                        </div>
                        
                        <!-- Additional event details -->
                        <div v-if="hasAdditionalDetails(event)" class="mt-2">
                            <details class="text-xs text-gray-600">
                                <summary class="cursor-pointer hover:text-gray-800">
                                    View details
                                </summary>
                                <div class="mt-2 p-3 bg-gray-50 rounded border">
                                    <pre class="whitespace-pre-wrap">{{ JSON.stringify(event.properties, null, 2) }}</pre>
                                </div>
                            </details>
                        </div>
                    </div>
                    
                    <!-- Event type indicator -->
                    <div class="shrink-0">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                              :class="getEventBadgeClass(event.event)">
                            {{ getEventType(event.event) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Load more button -->
            <div v-if="events.length >= 20" class="p-6 border-t border-gray-200 text-center">
                <button
                    @click="loadMore"
                    :disabled="loading"
                    class="text-green-600 hover:text-green-700 font-medium text-sm"
                >
                    <span v-if="loading">Loading...</span>
                    <span v-else>Load More</span>
                </button>
            </div>
        </div>

        <!-- Security Tips -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex">
                <InformationCircleIcon class="w-6 h-6 text-blue-400 shrink-0" />
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 mb-2">Security Tips</h3>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• Review this activity regularly for any suspicious behavior</li>
                        <li>• Use passkeys instead of passwords when possible</li>
                        <li>• Only grant app permissions you trust and need</li>
                        <li>• Sign out from shared or public devices</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import {
    ShieldCheckIcon,
    KeyIcon,
    ArrowRightOnRectangleIcon as SignInIcon,
    ArrowLeftOnRectangleIcon as SignOutIcon,
    CheckCircleIcon,
    XCircleIcon,
    InformationCircleIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    events: Array
})

const loading = ref(false)

const getEventIcon = (eventType) => {
    const icons = {
        'passkey_added': KeyIcon,
        'passkey_removed': KeyIcon,
        'passkey_authentication': KeyIcon,
        'passwordless_login': SignInIcon,
        'consent_granted': CheckCircleIcon,
        'consent_denied': XCircleIcon,
        'unusual_activity': ExclamationTriangleIcon
    }
    return icons[eventType] || ShieldCheckIcon
}

const getEventIconClass = (eventType) => {
    const classes = {
        'passkey_added': 'bg-green-100 text-green-600',
        'passkey_removed': 'bg-red-100 text-red-600',
        'passkey_authentication': 'bg-blue-100 text-blue-600',
        'passwordless_login': 'bg-blue-100 text-blue-600',
        'consent_granted': 'bg-green-100 text-green-600',
        'consent_denied': 'bg-red-100 text-red-600',
        'unusual_activity': 'bg-yellow-100 text-yellow-600'
    }
    return classes[eventType] || 'bg-gray-100 text-gray-600'
}

const getEventBadgeClass = (eventType) => {
    const classes = {
        'passkey_added': 'bg-green-100 text-green-800',
        'passkey_removed': 'bg-red-100 text-red-800',
        'passkey_authentication': 'bg-blue-100 text-blue-800',
        'passwordless_login': 'bg-blue-100 text-blue-800',
        'consent_granted': 'bg-green-100 text-green-800',
        'consent_denied': 'bg-red-100 text-red-800',
        'unusual_activity': 'bg-yellow-100 text-yellow-800'
    }
    return classes[eventType] || 'bg-gray-100 text-gray-800'
}

const getEventType = (eventType) => {
    const types = {
        'passkey_added': 'Passkey',
        'passkey_removed': 'Passkey',
        'passkey_authentication': 'Authentication',
        'passwordless_login': 'Login',
        'consent_granted': 'Consent',
        'consent_denied': 'Consent',
        'unusual_activity': 'Security'
    }
    return types[eventType] || 'Security'
}

const formatTime = (timestamp) => {
    const date = new Date(timestamp)
    const now = new Date()
    const diffInHours = Math.floor((now - date) / (1000 * 60 * 60))
    
    if (diffInHours < 1) {
        const diffInMinutes = Math.floor((now - date) / (1000 * 60))
        return diffInMinutes <= 1 ? 'Just now' : `${diffInMinutes}m ago`
    } else if (diffInHours < 24) {
        return `${diffInHours}h ago`
    } else {
        const diffInDays = Math.floor(diffInHours / 24)
        if (diffInDays < 7) {
            return `${diffInDays}d ago`
        } else {
            return date.toLocaleDateString()
        }
    }
}

const formatUserAgent = (userAgent) => {
    // Simple user agent parsing for display
    if (userAgent.includes('Chrome')) {
        return 'Chrome Browser'
    } else if (userAgent.includes('Firefox')) {
        return 'Firefox Browser'
    } else if (userAgent.includes('Safari') && !userAgent.includes('Chrome')) {
        return 'Safari Browser'
    } else if (userAgent.includes('Edge')) {
        return 'Edge Browser'
    } else {
        return 'Unknown Browser'
    }
}

const showUserAgent = (userAgent) => {
    return userAgent && userAgent.length > 0
}

const hasAdditionalDetails = (event) => {
    return event.properties && Object.keys(event.properties).length > 0
}

const loadMore = async () => {
    loading.value = true
    // In a real implementation, you'd make an API call to load more events
    // For now, this is just a placeholder
    setTimeout(() => {
        loading.value = false
    }, 1000)
}
</script>