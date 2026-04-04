<template>
    <div class="flex items-center gap-3 py-2.5 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 group">
        <Link :href="route('directory.members.show', member.hashid)" class="shrink-0">
            <div class="h-8 w-8 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                <img v-if="member.avatar" :src="member.avatar" :alt="member.name" class="h-full w-full object-cover" />
                <div v-else class="h-full w-full flex items-center justify-center text-xs font-medium text-gray-500 dark:text-gray-400">
                    {{ member.name.charAt(0).toUpperCase() }}
                </div>
            </div>
        </Link>
        <div class="flex-1 min-w-0">
            <Link :href="route('directory.members.show', member.hashid)" class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:underline truncate block">
                {{ member.name }}
            </Link>
            <span v-if="member.title" class="text-xs text-gray-500 dark:text-gray-400">{{ member.title }}</span>
        </div>
        <Badge v-if="isLead" variant="secondary" class="shrink-0 text-xs">
            {{ levelLabel }}
        </Badge>
        <slot name="actions" />
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { Badge } from '@/Components/ui/badge'

const props = defineProps({ member: Object })

const levelValue = computed(() => {
    const l = props.member.level
    return typeof l === 'object' ? l.value ?? l : l
})

const isLead = computed(() => ['division_director', 'director', 'team_lead'].includes(levelValue.value))

const levelLabel = computed(() => levelValue.value ? trans(`level_${levelValue.value}`) : '')
</script>
