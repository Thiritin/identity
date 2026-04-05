<template>
    <div class="grid md:grid-cols-4 gap-6 md:gap-10">
        <!-- Left sidebar nav -->
        <div class="md:col-span-1">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1 truncate">{{ app.client_name }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 font-mono mb-4 truncate">{{ app.client_id }}</p>
            <nav class="space-y-1">
                <Link
                    v-for="item in visibleItems"
                    :key="item.key"
                    :href="route(item.route, app.id)"
                    class="flex items-center justify-between rounded-md px-3 py-2 text-sm transition-colors"
                    :class="activeKey === item.key
                        ? 'bg-gray-100 dark:bg-gray-800 font-medium text-gray-900 dark:text-gray-100'
                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-gray-100'"
                >
                    {{ item.label }}
                    <ChevronRight v-if="activeKey === item.key" class="h-4 w-4 shrink-0" />
                </Link>
            </nav>
        </div>

        <!-- Main content -->
        <div class="md:col-span-3">
            <slot />
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { ChevronRight } from 'lucide-vue-next'
import { computed } from 'vue'
import { sidebarItems } from './sidebar.js'

const props = defineProps({
    app: {
        type: Object,
        required: true,
    },
    activeKey: {
        type: String,
        required: true,
    },
})

const visibleItems = computed(() =>
    sidebarItems.filter(item => !item.firstPartyOnly || props.app.first_party)
)
</script>
