<template>
    <Head :title="$t('tab_directory')" />
    <div class="space-y-8">
            <!-- My Groups -->
            <section v-if="myMemberships.length > 0">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">
                    {{ $t('directory_my_memberships') }}
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <Link
                        v-for="group in myMemberships"
                        :key="group.hashid"
                        :href="route('directory.show', group.slug)"
                        class="flex items-center justify-between gap-3 px-4 py-3 rounded-lg border border-primary/30 bg-primary/5 dark:border-primary/20 dark:bg-primary/10 hover:bg-primary/10 dark:hover:bg-primary/20 transition-colors"
                    >
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ group.name }}<DevHashid :id="group.hashid" /></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ group.title || levelLabel(group.level) || group.type }}
                            </div>
                        </div>
                        <Badge variant="outline" class="shrink-0 capitalize">{{ group.type }}</Badge>
                    </Link>
                </div>
            </section>

            <!-- Departments grouped by Division -->
            <section v-for="division in divisions" :key="division.hashid">
                <div class="flex items-center gap-2 mb-3">
                    <Link
                        :href="route('directory.show', division.slug)"
                        class="text-sm font-semibold text-gray-900 dark:text-gray-100 hover:text-primary transition-colors"
                    >
                        {{ division.name }}<DevHashid :id="division.hashid" />
                    </Link>
                    <Badge variant="secondary" class="text-xs">{{ division.member_count }}</Badge>
                </div>
                <div v-if="division.departments.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <Link
                        v-for="dept in division.departments"
                        :key="dept.hashid"
                        :href="route('directory.show', dept.slug)"
                        class="flex items-center justify-between gap-3 px-4 py-3 rounded-lg border transition-colors"
                        :class="dept.is_mine
                            ? 'border-primary/30 bg-primary/5 dark:border-primary/20 dark:bg-primary/10 hover:bg-primary/10 dark:hover:bg-primary/20'
                            : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-white/5'"
                    >
                        <div class="min-w-0 flex items-center gap-2">
                            <component :is="iconMap[dept.icon]" v-if="dept.icon && iconMap[dept.icon]" class="h-4 w-4 shrink-0 text-gray-400" />
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ dept.name }}<DevHashid :id="dept.hashid" /></div>
                            <span v-if="dept.is_mine" class="h-1.5 w-1.5 rounded-full bg-primary shrink-0" />
                        </div>
                        <Badge variant="secondary" class="shrink-0">{{ dept.member_count }}</Badge>
                    </Link>
                </div>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('directory_no_departments') }}
                </p>
            </section>

            <!-- Orphan departments (no parent division) -->
            <section v-if="orphanDepartments.length > 0">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">
                    {{ $t('directory_other_groups') }}
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <Link
                        v-for="dept in orphanDepartments"
                        :key="dept.hashid"
                        :href="route('directory.show', dept.slug)"
                        class="flex items-center justify-between gap-3 px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                    >
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ dept.name }}<DevHashid :id="dept.hashid" /></div>
                        </div>
                        <Badge variant="secondary" class="shrink-0">{{ dept.member_count }}</Badge>
                    </Link>
                </div>
            </section>

            <!-- System groups (dev mode only) -->
            <section v-if="devMode.enabled && systemMemberships.length > 0">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">
                    System groups
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div
                        v-for="group in systemMemberships"
                        :key="group.hashid"
                        class="flex items-center justify-between gap-3 px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700"
                    >
                        <div class="min-w-0 flex items-center gap-2">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                {{ group.name }}
                            </div>
                            <DevHashid :id="group.hashid" />
                        </div>
                        <Badge variant="outline" class="shrink-0 capitalize">{{ group.type }}</Badge>
                    </div>
                </div>
            </section>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { Badge } from '@/Components/ui/badge'
import { iconMap } from './Components/iconMap'
import DevHashid from '@/Components/DevHashid.vue'
import { useDevMode } from '@/Composables/useDevMode'
const devMode = useDevMode()

defineProps({
    myMemberships: Array,
    divisions: Array,
    orphanDepartments: Array,
    systemMemberships: Array,
})

function levelLabel(level) {
    const val = typeof level === 'object' ? level.value ?? level : level
    return val ? trans(`level_${val}`) : ''
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'
export default { layout: AccountLayout }
</script>
