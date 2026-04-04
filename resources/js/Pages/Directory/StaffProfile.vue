<template>
    <Head :title="profileUser.name" />
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center gap-4 mb-6">
            <div class="h-16 w-16 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden shrink-0">
                <img v-if="profileUser.avatar" :src="profileUser.avatar" :alt="profileUser.name" class="h-full w-full object-cover" />
                <div v-else class="h-full w-full flex items-center justify-center text-lg font-medium text-gray-500 dark:text-gray-400">
                    {{ profileUser.name.charAt(0).toUpperCase() }}
                </div>
            </div>
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ profileUser.name }}</h1>
                <p v-if="profileUser.credit_as" class="text-sm text-gray-500 dark:text-gray-400">{{ profileUser.credit_as }}</p>
            </div>
        </div>

        <section v-if="groups.length > 0" class="mb-6">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ $t('staff_profile_roles') }}</h2>
            <div class="space-y-2">
                <Link
                    v-for="group in groups"
                    :key="group.hashid"
                    :href="route('directory.show', group.slug)"
                    class="flex items-center justify-between px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                >
                    <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ group.name }}</div>
                        <div v-if="group.title" class="text-xs text-gray-500 dark:text-gray-400">{{ group.title }}</div>
                    </div>
                    <Badge v-if="isLead(group.level)" variant="secondary" class="text-xs capitalize">
                        {{ formatLevel(group.level) }}
                    </Badge>
                </Link>
            </div>
        </section>

        <section v-if="Object.keys(visibleFields).length > 0" class="mb-6">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ $t('staff_profile_personal_info') }}</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div v-if="visibleFields.firstname" class="px-4 py-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $t('staff_profile_firstname') }}</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ visibleFields.firstname }}</dd>
                </div>
                <div v-if="visibleFields.lastname" class="px-4 py-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $t('staff_profile_lastname') }}</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ visibleFields.lastname }}</dd>
                </div>
                <div v-if="visibleFields.birthdate" class="px-4 py-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $t('staff_profile_birthdate') }}</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ visibleFields.birthdate }}</dd>
                </div>
                <div v-if="visibleFields.phone" class="px-4 py-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $t('staff_profile_phone') }}</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ visibleFields.phone }}</dd>
                </div>
                <div v-if="visibleFields.telegram" class="px-4 py-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $t('staff_profile_telegram') }}</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">@{{ visibleFields.telegram }}</dd>
                </div>
            </dl>
        </section>

        <section v-if="profileUser.spoken_languages?.length" class="mb-6">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ $t('staff_profile_skills') }}</h2>
            <div class="px-4 py-3 rounded-lg bg-gray-50 dark:bg-white/5">
                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $t('staff_profile_spoken_languages') }}</dt>
                <dd class="flex flex-wrap gap-1 mt-1">
                    <Badge v-for="lang in profileUser.spoken_languages" :key="lang" variant="secondary" class="text-xs">{{ lang }}</Badge>
                </dd>
            </div>
        </section>

        <section class="mb-6">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ $t('convention_attendance') }}</h2>
            <ConventionAttendanceEditor
                :attendance="conventionAttendance"
                :all-conventions="allConventions"
                :can-manage="canManageAttendance"
                :readonly="!canManageAttendance"
                :endpoint="canManageAttendance ? route('directory.members.conventions', profileUser.hashid) : ''"
            />
        </section>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Badge } from '@/Components/ui/badge'
import ConventionAttendanceEditor from '@/Components/ConventionAttendanceEditor.vue'

defineProps({
    profileUser: Object,
    groups: Array,
    visibleFields: Object,
    conventionAttendance: Array,
    allConventions: Array,
    canManageAttendance: Boolean,
})

function isLead(level) {
    return ['division_director', 'director', 'team_lead'].includes(level)
}

function formatLevel(level) {
    return level.replace(/_/g, ' ')
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'
export default { layout: AccountLayout }
</script>
