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

        <section v-if="nda?.can_manage" class="mb-6">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ $t('nda_status') }}</h2>
            <div class="px-4 py-3 rounded-lg bg-gray-50 dark:bg-white/5 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <span v-if="ndaVerifiedAt" class="text-sm text-green-600 dark:text-green-400 font-medium">
                            {{ $t('nda_verified') }} — {{ new Date(ndaVerifiedAt).toLocaleDateString() }}
                        </span>
                        <span v-else class="text-sm text-amber-600 dark:text-amber-400 font-medium">
                            {{ $t('nda_not_verified') }}
                        </span>
                    </div>
                </div>

                <div v-if="ndaLastResult === true" class="text-sm text-green-600 dark:text-green-400">
                    {{ $t('nda_check_result_signed') }}
                </div>
                <div v-else-if="ndaLastResult === false" class="text-sm text-red-600 dark:text-red-400">
                    {{ $t('nda_check_result_not_signed') }}
                </div>
                <div v-else-if="ndaLastResult === 'sent'" class="text-sm text-green-600 dark:text-green-400">
                    {{ $t('nda_sent_success') }}
                </div>

                <div class="flex gap-2">
                    <Button size="sm" variant="outline" :disabled="ndaChecking" @click="checkNda">
                        {{ ndaChecking ? $t('nda_checking') : $t('nda_check') }}
                    </Button>
                    <Button v-if="!ndaVerifiedAt" size="sm" variant="outline" :disabled="ndaSending" @click="sendNda">
                        {{ ndaSending ? $t('nda_sending') : $t('nda_send') }}
                    </Button>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { Head, Link } from '@inertiajs/vue3'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import ConventionAttendanceEditor from '@/Components/ConventionAttendanceEditor.vue'

const props = defineProps({
    profileUser: Object,
    groups: Array,
    visibleFields: Object,
    conventionAttendance: Array,
    allConventions: Array,
    canManageAttendance: Boolean,
    nda: Object,
})

const ndaVerifiedAt = ref(props.nda?.verified_at)
const ndaChecking = ref(false)
const ndaSending = ref(false)
const ndaLastResult = ref(null)

function checkNda() {
    ndaChecking.value = true
    ndaLastResult.value = null

    axios.post(route('directory.members.nda.check', props.profileUser.hashid))
        .then((response) => {
            ndaLastResult.value = response.data.signed
            if (response.data.nda_verified_at) {
                ndaVerifiedAt.value = response.data.nda_verified_at
            }
        })
        .finally(() => { ndaChecking.value = false })
}

function sendNda() {
    ndaSending.value = true

    axios.post(route('directory.members.nda.send', props.profileUser.hashid))
        .then(() => {
            ndaLastResult.value = 'sent'
        })
        .finally(() => { ndaSending.value = false })
}

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
