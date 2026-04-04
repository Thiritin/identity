<template>
    <Head :title="$t('developers_title')" />
    <div class="space-y-8">
        <!-- Documentation Link -->
        <div class="grid md:grid-cols-3 gap-6 md:gap-10">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('developers_docs_title') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('developers_docs_subtitle') }}</p>
            </div>
            <div class="md:col-span-2">
                <a
                    href="https://docs.identity.eurofurence.org/"
                    target="_blank"
                    class="inline-flex items-center gap-2 text-blue-700 dark:text-blue-400 hover:underline font-medium"
                >
                    <ExternalLink class="h-4 w-4" />
                    docs.identity.eurofurence.org
                </a>
            </div>
        </div>

        <!-- App Management (developers only) -->
        <template v-if="isDeveloper">
            <div class="grid md:grid-cols-3 gap-6 md:gap-10">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('apps_title') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('apps_subtitle') }}</p>
                </div>
                <div class="md:col-span-2">
                    <div class="flex justify-end mb-4">
                        <Button as-child>
                            <Link :href="route('developers.create')">{{ $t('apps_create') }}</Link>
                        </Button>
                    </div>

                    <div v-if="apps.length" class="divide-y divide-gray-200 dark:divide-gray-700">
                        <Link v-for="app in apps" :key="app.id"
                              :href="route('developers.show', app.id)"
                              class="flex items-center justify-between py-4 first:pt-0 group">
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ app.client_name }}</p>
                                    <span v-if="!app.approved" class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200">
                                        {{ $t('apps_personal_use') }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 font-mono">{{ app.client_id }}</p>
                            </div>
                            <ChevronRight class="h-5 w-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 shrink-0" />
                        </Link>
                    </div>

                    <div v-else class="flex items-center justify-center py-16">
                        <p class="text-gray-400 dark:text-gray-500">{{ $t('apps_no_apps') }}</p>
                    </div>
                </div>
            </div>
        </template>

        <!-- Non-developer CTA -->
        <template v-else>
            <div class="grid md:grid-cols-3 gap-6 md:gap-10">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('developers_build_apps_title') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('developers_build_apps_subtitle') }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $t('developers_contact_description') }}
                    </p>
                    <p class="mt-2 font-medium text-gray-900 dark:text-gray-100">
                        <a href="mailto:identity@eurofurence.org" class="text-blue-700 dark:text-blue-400 hover:underline">identity@eurofurence.org</a>
                    </p>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { ChevronRight, ExternalLink } from 'lucide-vue-next'

defineProps({
    apps: Array,
    isDeveloper: Boolean,
})
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
