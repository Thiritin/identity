<template>
    <Head :title="$t('apps_title')" />
    <div class="grid md:grid-cols-3 gap-6 md:gap-10">
        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('apps_title') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('apps_subtitle') }}</p>
        </div>
        <div class="md:col-span-2">
            <div class="flex justify-end mb-4">
                <Button as-child>
                    <Link :href="route('settings.apps.create')">{{ $t('apps_create') }}</Link>
                </Button>
            </div>

            <div v-if="apps.length" class="divide-y divide-gray-200 dark:divide-gray-700">
                <Link v-for="app in apps" :key="app.id"
                      :href="route('settings.apps.show', app.id)"
                      class="flex items-center justify-between py-4 first:pt-0 group">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ app.client_name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-mono">{{ app.client_id }}</p>
                    </div>
                    <ChevronRight class="h-5 w-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 shrink-0" />
                </Link>
            </div>

            <div v-else class="flex items-center justify-center py-16 text-center">
                <p class="text-gray-400 dark:text-gray-500">{{ $t('apps_no_apps') }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { ChevronRight } from 'lucide-vue-next'

defineProps({
    apps: Array,
})
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
