<template>
    <div v-if="groups.length > 0">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                {{ $t('directory_sub_groups') }}
            </h3>
            <slot name="actions" />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <Link
                v-for="group in groups"
                :key="group.hashid"
                :href="route('directory.show', group.slug)"
                class="flex items-center justify-between gap-3 px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
            >
                <div class="min-w-0">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ group.name }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ group.type }}</div>
                </div>
                <Badge variant="secondary" class="shrink-0">{{ group.member_count }}</Badge>
            </Link>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { Badge } from '@/Components/ui/badge'

defineProps({ groups: Array })
</script>
