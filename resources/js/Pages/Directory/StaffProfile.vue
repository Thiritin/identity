<template>
    <Head :title="profileUser.name" />
    <div class="max-w-2xl mx-auto">
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 mb-6 flex-wrap">
            <template v-for="(crumb, i) in breadcrumbs" :key="i">
                <span v-if="i > 0" class="text-gray-300 dark:text-gray-600">&#10095;</span>
                <Link v-if="crumb.href" :href="crumb.href" class="hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                    {{ crumb.label }}
                </Link>
                <span v-else class="text-gray-900 dark:text-gray-100 font-medium">{{ crumb.label }}</span>
            </template>
        </nav>

        <!-- Profile header -->
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

        <!-- Role in [Group] (group-scoped, read-only with edit button for directors) -->
        <section v-if="groupMembership" class="mb-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {{ $t('staff_profile_role_in_group', { group: group.name }) }}
                    </h2>
                <Button v-if="canEdit" variant="ghost" size="sm" @click="showEditMember = true">
                    <Pencil class="h-3.5 w-3.5 mr-1" /> {{ $t('directory_edit_group') }}
                </Button>
            </div>
            <div class="px-4 py-3 rounded-lg bg-gray-50 dark:bg-white/5 space-y-2">
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $t('staff_profile_level') }}</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $t('level_' + groupMembership.level) }}</dd>
                </div>
                <div v-if="groupMembership.title">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $t('staff_profile_title') }}</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ groupMembership.title }}</dd>
                </div>
            </div>
        </section>

        <MemberEditModal
            v-if="canEdit"
            :open="showEditMember"
            :member="memberForModal"
            :group-hashid="group.hashid"
            :assignable-levels="assignableLevels"
            @close="showEditMember = false"
        />

        <!-- All roles (global) -->
        <section v-if="groups.length > 0" class="mb-6">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">
                {{ $t('staff_profile_roles') }}
            </h2>
            <div class="space-y-2">
                <Link
                    v-for="g in groups"
                    :key="g.hashid"
                    :href="route('directory.show', g.slug)"
                    class="flex items-center justify-between px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                >
                    <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ g.name }}</div>
                        <div v-if="g.title" class="text-xs text-gray-500 dark:text-gray-400">{{ g.title }}</div>
                    </div>
                    <Badge v-if="isLead(g.level)" variant="secondary" class="text-xs capitalize">
                        {{ formatLevel(g.level) }}
                    </Badge>
                </Link>
            </div>
        </section>

        <!-- Personal info (global) -->
        <section v-if="Object.keys(visibleFields).length > 0" class="mb-6">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">
                {{ $t('staff_profile_personal_info') }}
            </h2>
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
                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ formattedBirthdate }}
                        <span class="text-gray-500 dark:text-gray-400 font-normal">({{ age }})</span>
                    </dd>
                </div>
                <div v-if="visibleFields.phone" class="px-4 py-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $t('staff_profile_phone') }}</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ visibleFields.phone }}</dd>
                </div>
                <div v-if="visibleFields.telegram" class="px-4 py-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $t('staff_profile_telegram') }}</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        <a :href="'https://t.me/' + visibleFields.telegram" target="_blank" class="text-primary-600 hover:text-primary-500 dark:text-primary-300 dark:hover:text-primary-200">
                            @{{ visibleFields.telegram }}
                        </a>
                    </dd>
                </div>
                <div v-if="visibleFields.address_line1 !== undefined" class="sm:col-span-2 px-4 py-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $t('staff_profile_address') }}</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100 whitespace-pre-line">
                        <template v-if="formattedAddress">{{ formattedAddress }}</template>
                        <span v-else class="text-gray-400 italic">—</span>
                    </dd>
                </div>
                <div v-if="visibleFields.emergency_contact_name !== undefined" class="sm:col-span-2 px-4 py-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $t('staff_profile_emergency_contact') }}</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100 space-y-0.5">
                        <div v-if="visibleFields.emergency_contact_name">{{ visibleFields.emergency_contact_name }}</div>
                        <div v-if="visibleFields.emergency_contact_phone">
                            <a :href="'tel:' + visibleFields.emergency_contact_phone" class="text-primary-600 hover:text-primary-500 dark:text-primary-300 dark:hover:text-primary-200">
                                {{ visibleFields.emergency_contact_phone }}
                            </a>
                        </div>
                        <div v-if="visibleFields.emergency_contact_telegram">
                            <a :href="'https://t.me/' + visibleFields.emergency_contact_telegram" target="_blank" class="text-primary-600 hover:text-primary-500 dark:text-primary-300 dark:hover:text-primary-200">
                                @{{ visibleFields.emergency_contact_telegram }}
                            </a>
                        </div>
                    </dd>
                </div>
            </dl>
        </section>

        <!-- Spoken languages (global) -->
        <section v-if="profileUser.spoken_languages?.length" class="mb-6">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">
                {{ $t('staff_profile_skills') }}
            </h2>
            <div class="px-4 py-3 rounded-lg bg-gray-50 dark:bg-white/5">
                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $t('staff_profile_spoken_languages') }}</dt>
                <dd class="flex flex-wrap gap-1 mt-1">
                    <Badge v-for="lang in profileUser.spoken_languages" :key="lang" variant="secondary" class="text-xs">{{ displayLanguage(lang) }}</Badge>
                </dd>
            </div>
        </section>

        <!-- Convention attendance (global, editable by directors) -->
        <section class="mb-6">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">
                {{ $t('convention_attendance') }}
            </h2>
            <ConventionAttendanceEditor
                :attendance="conventionAttendance"
                :all-conventions="allConventions"
                :can-manage="canEdit"
                :readonly="!canEdit"
                :endpoint="canEdit ? route('directory.members.conventions', { slug: group.slug, user: profileUser.hashid }) : ''"
            />
        </section>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import { Pencil } from 'lucide-vue-next'
import ConventionAttendanceEditor from '@/Components/ConventionAttendanceEditor.vue'
import MemberEditModal from './Components/MemberEditModal.vue'

const props = defineProps({
    breadcrumbs: Array,
    group: Object,
    groupMembership: Object,
    canEdit: Boolean,
    assignableLevels: Array,
    profileUser: Object,
    groups: Array,
    visibleFields: Object,
    conventionAttendance: Array,
    allConventions: Array,
})

const showEditMember = ref(false)

const formattedBirthdate = computed(() => {
    if (!props.visibleFields?.birthdate) return ''
    return new Date(props.visibleFields.birthdate).toLocaleDateString(undefined, {
        year: 'numeric', month: 'long', day: 'numeric',
    })
})

const formattedAddress = computed(() => {
    const v = props.visibleFields ?? {}
    if (v.address_line1 === undefined) return ''
    const lines = [
        v.address_line1,
        v.address_line2,
        [v.postal_code, v.city].filter(Boolean).join(' '),
        v.country,
    ].filter(Boolean)
    return lines.join('\n')
})

const age = computed(() => {
    if (!props.visibleFields?.birthdate) return ''
    const birth = new Date(props.visibleFields.birthdate)
    const now = new Date()
    let years = now.getFullYear() - birth.getFullYear()
    const monthDiff = now.getMonth() - birth.getMonth()
    if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < birth.getDate())) {
        years--
    }
    return years
})

const displayLanguage = (code) => {
    try {
        const name = new Intl.DisplayNames(undefined, { type: 'language' }).of(code)
        return name ? name.charAt(0).toUpperCase() + name.slice(1) : code
    } catch {
        return code
    }
}

const memberForModal = computed(() => ({
    hashid: props.profileUser.hashid,
    name: props.profileUser.name,
    level: props.groupMembership?.level,
    title: props.groupMembership?.title,
    can_manage_members: props.groupMembership?.can_manage_members,
}))

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
