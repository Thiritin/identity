<template>
    <Head :title="$t('dashboard')" />

    <TooltipProvider>
        <!-- Registration Hero -->
        <div
            v-if="registration"
            class="mb-8 overflow-hidden rounded-2xl bg-gradient-to-r from-primary-600 to-primary-500 p-6 shadow-lg dark:from-primary-800 dark:to-primary-700"
        >
            <div class="flex items-center gap-5">
                <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-white/20 shadow-inner">
                    <img
                        v-if="registration.image_url"
                        :src="registration.image_url"
                        :alt="registration.name"
                        class="h-full w-full object-cover"
                    />
                    <div
                        v-else
                        class="flex h-full w-full items-center justify-center text-2xl font-bold text-white/80"
                    >
                        {{ registration.name?.charAt(0)?.toUpperCase() }}
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-semibold text-white">
                        {{ registration.name }}
                    </h2>
                    <p v-if="registration.description" class="text-sm text-white/80">
                        {{ registration.description }}
                    </p>
                </div>
                <a
                    :href="registration.url"
                    class="shrink-0 rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-primary-700 shadow-sm transition-colors hover:bg-white/90 dark:bg-white/95 dark:text-primary-800 dark:hover:bg-white/80"
                >
                    {{ $t('registration_cta') }}
                </a>
            </div>
        </div>

        <!-- Pinned Apps -->
        <div v-if="pinned.length" class="flex flex-wrap gap-6 pb-8">
            <AppTile v-for="app in pinned" :key="app.id" :app="app" />
        </div>

        <!-- Category Sections -->
        <div v-for="category in categories" :key="category.id" class="pb-8">
            <h2 class="pb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ category.name }}
            </h2>
            <div class="flex flex-wrap gap-6">
                <AppTile v-for="app in category.apps" :key="app.id" :app="app" />
            </div>
        </div>

        <!-- Uncategorized Apps -->
        <div v-if="uncategorized.length" class="pb-8">
            <h2
                v-if="categories.length"
                class="pb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
            >
                {{ $t('other') }}
            </h2>
            <div class="flex flex-wrap gap-6">
                <AppTile v-for="app in uncategorized" :key="app.id" :app="app" />
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-if="!pinned.length && !categories.length && !uncategorized.length"
            class="py-12 text-center text-gray-500 dark:text-gray-400"
        >
            {{ $t('no_applications_available') }}
        </div>
    </TooltipProvider>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import AppTile from '@/Components/AppTile.vue'
import { TooltipProvider } from '@/Components/ui/tooltip'

defineProps({
    registration: Object,
    pinned: Array,
    categories: Array,
    uncategorized: Array,
})
</script>

<script>
import AccountLayout from '../Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
