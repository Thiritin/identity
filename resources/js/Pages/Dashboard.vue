<template>
    <Head title="Dashboard" />
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a
            v-for="app in apps"
            :key="app.id"
            :href="app.url"
            class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800"
        >
            <component
                :is="loadIconComponent(app.icon)"
                class="h-12 min-w-[48px] fill-current text-primary-600 dark:text-primary-300"
            />
            <div class="min-w-0">
                <h2 class="font-medium text-gray-900 dark:text-gray-100">{{ app.name }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ app.description }}</p>
            </div>
        </a>
    </div>
    <div v-if="apps.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-12">
        No applications available.
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { defineAsyncComponent } from 'vue'

defineProps({
    apps: Array,
})

const loadIconComponent = (name) => defineAsyncComponent(() => import(`../Components/Icons/${name}.vue`))
</script>

<script>
import AccountLayout from '../Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
