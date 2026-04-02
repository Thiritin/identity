<template>
    <Head :title="$t('dashboard')" />

    <TooltipProvider>
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
