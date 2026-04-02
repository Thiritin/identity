<template>
    <Head :title="$t('sessions_title')" />
    <div>
        <Link :href="route('settings.security')" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-4">
            <ArrowLeft class="h-4 w-4" />
            {{ $t('security') }}
        </Link>

        <SettingsHeader>{{ $t('sessions_title') }}</SettingsHeader>
        <SettingsSubHeader class="mb-4">{{ $t('sessions_subtitle') }}</SettingsSubHeader>

        <div v-if="sessions.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
            {{ $t('sessions_empty') }}
        </div>

        <div v-else class="space-y-3">
            <div v-for="session in sessions" :key="session.id"
                 class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-sm">{{ session.app_name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ session.ip_address }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ session.user_agent }}</p>
                    </div>
                    <div class="text-right text-xs text-gray-500 dark:text-gray-400">
                        <p>{{ session.last_seen_at }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import SettingsHeader from '@/Components/Settings/SettingsHeader.vue'
import SettingsSubHeader from '@/Components/Settings/SettingsSubHeader.vue'

defineProps({
    sessions: Array,
    currentSessionId: String,
})
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
