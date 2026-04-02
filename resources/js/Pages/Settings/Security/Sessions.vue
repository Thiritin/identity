<template>
    <Head :title="$t('security_sessions_header')" />
    <div>
        <Link :href="route('settings.security')" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-4">
            <ArrowLeft class="h-4 w-4" />
            {{ $t('security') }}
        </Link>

        <SettingsHeader>{{ $t('security_sessions_header') }}</SettingsHeader>
        <SettingsSubHeader class="mb-3">{{ $t('security_sessions_subtitle') }}</SettingsSubHeader>

        <div v-if="sessions.length === 0" class="py-8 text-center text-gray-500 dark:text-gray-400">
            {{ $t('security_sessions_empty') }}
        </div>

        <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
            <div v-for="session in sessions" :key="session.id" class="flex items-center justify-between py-4">
                <div class="flex items-center gap-3">
                    <Monitor class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">
                            {{ parseUserAgent(session.user_agent) }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ session.app_name ?? $t('security_sessions_header') }}
                            <span v-if="session.ip_address"> &middot; {{ session.ip_address }}</span>
                            <span v-if="session.last_seen_at"> &middot; {{ session.last_seen_at }}</span>
                        </p>
                    </div>
                </div>
                <div>
                    <Badge v-if="session.session_id === currentSessionId" variant="default">
                        {{ $t('security_sessions_current') }}
                    </Badge>
                    <Button
                        v-else
                        variant="outline"
                        size="sm"
                        :disabled="revoking === session.id"
                        @click="revokeSession(session)"
                    >
                        {{ $t('security_sessions_end') }}
                    </Button>
                </div>
            </div>
        </div>

        <div v-if="otherSessionsExist" class="mt-6">
            <Button
                variant="destructive"
                :disabled="revokingAll"
                @click="revokeAllOthers"
            >
                {{ $t('security_sessions_end_all') }}
            </Button>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { ArrowLeft, Monitor } from 'lucide-vue-next'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import SettingsHeader from '@/Components/Settings/SettingsHeader.vue'
import SettingsSubHeader from '@/Components/Settings/SettingsSubHeader.vue'

const props = defineProps({
    sessions: Array,
    currentSessionId: String,
})

const revoking = ref(null)
const revokingAll = ref(false)

const otherSessionsExist = computed(() =>
    props.sessions.some(s => s.session_id !== props.currentSessionId)
)

function parseUserAgent(ua) {
    if (!ua) {
        return 'Unknown device'
    }

    let browser = 'Unknown browser'
    let os = 'Unknown OS'

    if (ua.includes('Firefox/')) {
        browser = 'Firefox'
    } else if (ua.includes('Edg/')) {
        browser = 'Edge'
    } else if (ua.includes('Chrome/')) {
        browser = 'Chrome'
    } else if (ua.includes('Safari/')) {
        browser = 'Safari'
    }

    if (ua.includes('Windows')) {
        os = 'Windows'
    } else if (ua.includes('Macintosh') || ua.includes('Mac OS')) {
        os = 'macOS'
    } else if (ua.includes('Linux')) {
        os = 'Linux'
    } else if (ua.includes('iPhone') || ua.includes('iPad')) {
        os = 'iOS'
    } else if (ua.includes('Android')) {
        os = 'Android'
    }

    return `${browser} on ${os}`
}

function revokeSession(session) {
    if (!confirm(trans('security_sessions_end_confirm'))) {
        return
    }

    revoking.value = session.id
    router.delete(route('settings.security.sessions.destroy', session.id), {
        preserveScroll: true,
        onFinish: () => { revoking.value = null },
    })
}

function revokeAllOthers() {
    if (!confirm(trans('security_sessions_end_all_confirm'))) {
        return
    }

    revokingAll.value = true
    router.delete(route('settings.security.sessions.destroy-others'), {
        preserveScroll: true,
        onFinish: () => { revokingAll.value = false },
    })
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
