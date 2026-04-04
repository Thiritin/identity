<template>
    <Head :title="$t('my_data_title')" />

    <div class="space-y-8">
        <!-- Transparency Notice -->
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-5 dark:border-blue-800 dark:bg-blue-950/30">
            <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-200">{{ $t('my_data_transparency_title') }}</h3>
            <p class="mt-2 text-sm text-blue-800 dark:text-blue-300">{{ $t('my_data_transparency_body') }}</p>
            <p class="mt-2 text-sm text-blue-700 dark:text-blue-400">{{ $t('my_data_transparency_segmentation') }}</p>
            <div class="mt-3 text-xs text-blue-600 dark:text-blue-400 space-y-0.5">
                <p><span class="font-medium">{{ $t('my_data_responsible') }}:</span> {{ $t('my_data_responsible_entity') }}</p>
                <p><span class="font-medium">{{ $t('my_data_privacy_contact') }}:</span> <a href="mailto:datenschutz@eurofurence.de" class="underline">datenschutz@eurofurence.de</a></p>
            </div>
        </div>

        <!-- Profile Data -->
        <div class="grid md:grid-cols-3 gap-6 md:gap-10">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $t('my_data_profile_section') }}</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $t('my_data_profile_description') }}</p>
                <Link :href="route('settings.profile')" class="mt-2 inline-block text-xs text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                    {{ $t('my_data_edit_profile') }} &rarr;
                </Link>
            </div>
            <div class="md:col-span-2">
                <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                    <DataField :label="$t('my_data_nickname')" :value="profile.name" />
                    <DataField :label="$t('my_data_email')" :value="profile.email" />
                    <DataField :label="$t('my_data_profile_photo')" :value="profile.profilePhotoPath ? '✓' : null" />
                    <template v-if="isStaff">
                        <DataField :label="$t('my_data_firstname')" :value="profile.firstname" />
                        <DataField :label="$t('my_data_lastname')" :value="profile.lastname" />
                        <DataField :label="$t('my_data_birthdate')" :value="profile.birthdate" />
                        <DataField :label="$t('my_data_phone')" :value="profile.phone" />
                        <DataField :label="$t('my_data_telegram')" :value="profile.telegram" />
                        <DataField :label="$t('my_data_spoken_languages')" :value="profile.spokenLanguages?.join(', ')" />
                        <DataField :label="$t('my_data_credit_as')" :value="profile.creditAs" />
                        <DataField :label="$t('my_data_first_ef')" :value="profile.firstEurofurence" />
                        <DataField :label="$t('my_data_first_year_staff')" :value="profile.firstYearStaff" />
                    </template>
                </dl>
            </div>
        </div>

        <!-- Access Levels (staff only) -->
        <div v-if="isStaff" class="grid md:grid-cols-3 gap-6 md:gap-10 border-t border-gray-200/50 dark:border-gray-700/50 pt-8">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $t('my_data_access_levels_title') }}</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $t('my_data_access_levels_description') }}</p>
                <Link :href="route('settings.profile')" class="mt-2 inline-block text-xs text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                    {{ $t('my_data_edit_profile') }} &rarr;
                </Link>
            </div>
            <div class="md:col-span-2 space-y-2">
                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $t('my_data_visibility_all_staff') }}</p>
                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $t('my_data_visibility_my_departments') }}</p>
                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $t('my_data_visibility_leads_and_directors') }}</p>
                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $t('my_data_visibility_directors_only') }}</p>
            </div>
        </div>

        <!-- Group Memberships (staff only) -->
        <div v-if="isStaff && groups?.length" class="grid md:grid-cols-3 gap-6 md:gap-10 border-t border-gray-200/50 dark:border-gray-700/50 pt-8">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $t('my_data_groups_title') }}</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $t('my_data_groups_description') }}</p>
            </div>
            <div class="md:col-span-2">
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="group in groups" :key="group.name" class="py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ group.name }}</p>
                            <p v-if="group.title" class="text-xs text-gray-500 dark:text-gray-400">{{ group.title }}</p>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ group.level?.replace('_', ' ') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Convention History (staff only) -->
        <div v-if="isStaff && conventions?.length" class="grid md:grid-cols-3 gap-6 md:gap-10 border-t border-gray-200/50 dark:border-gray-700/50 pt-8">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $t('my_data_conventions_title') }}</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $t('my_data_conventions_description') }}</p>
            </div>
            <div class="md:col-span-2">
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="convention in conventions" :key="convention.year" class="py-3 flex items-center justify-between">
                        <span class="text-sm text-gray-900 dark:text-white">{{ convention.name }} ({{ convention.year }})</span>
                        <span class="text-xs px-2 py-0.5 rounded-full" :class="convention.isStaff ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'">
                            {{ convention.isStaff ? $t('my_data_conventions_staff') : $t('my_data_conventions_attendee') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Log -->
        <div class="grid md:grid-cols-3 gap-6 md:gap-10 border-t border-gray-200/50 dark:border-gray-700/50 pt-8">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $t('my_data_activity_title') }}</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $t('my_data_activity_description') }}</p>
            </div>
            <div class="md:col-span-2">
                <div v-if="activityLog.data.length === 0" class="py-4 text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('my_data_activity_no_entries') }}
                </div>
                <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="entry in activityLog.data" :key="entry.id" class="py-3" :class="{ 'bg-amber-50/50 dark:bg-amber-950/20 -mx-3 px-3 rounded': !entry.causedBySelf }">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ new Date(entry.createdAt).toLocaleString() }}
                            </span>
                            <span class="text-xs" :class="entry.causedBySelf ? 'text-gray-500 dark:text-gray-400' : 'text-amber-600 dark:text-amber-400 font-medium'">
                                {{ entry.causedBySelf ? $t('my_data_activity_you') : $t('my_data_activity_changed_by', { name: entry.causerName }) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">{{ entry.description }}</p>
                        <div v-if="entry.properties?.attributes" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <span v-for="(value, key) in entry.properties.attributes" :key="key" class="inline-block mr-3">
                                {{ key }}: <span class="text-gray-700 dark:text-gray-300">{{ value }}</span>
                            </span>
                        </div>
                    </div>
                </div>
                <!-- Pagination -->
                <div v-if="activityLog.links?.length > 3" class="mt-4 flex gap-1">
                    <Link
                        v-for="link in activityLog.links"
                        :key="link.label"
                        :href="link.url"
                        class="px-3 py-1 text-xs rounded border"
                        :class="link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800'"
                        v-html="link.label"
                        :preserve-scroll="true"
                    />
                </div>
            </div>
        </div>

        <!-- Connected Apps -->
        <div class="grid md:grid-cols-3 gap-6 md:gap-10 border-t border-gray-200/50 dark:border-gray-700/50 pt-8">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $t('my_data_apps_title') }}</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $t('my_data_apps_description') }}</p>
            </div>
            <div class="md:col-span-2">
                <div v-if="connectedApps === null" class="py-4 text-sm text-red-600 dark:text-red-400">
                    {{ $t('my_data_apps_error') }}
                </div>
                <div v-else-if="connectedApps.length === 0" class="py-4 text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('my_data_apps_no_apps') }}
                </div>
                <div v-else class="space-y-4">
                    <div v-for="app in connectedApps" :key="app.clientId" class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <img v-if="app.icon" :src="app.icon" :alt="app.name" class="h-8 w-8 rounded" />
                                <div v-else class="h-8 w-8 rounded bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xs text-gray-500">{{ app.name?.[0] }}</div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ app.name }}</p>
                                    <p v-if="app.description" class="text-xs text-gray-500 dark:text-gray-400">{{ app.description }}</p>
                                </div>
                            </div>
                            <a v-if="app.policyUri" :href="app.policyUri" target="_blank" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                {{ $t('my_data_apps_privacy_policy') }}
                            </a>
                        </div>
                        <div class="mt-3 space-y-1">
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ $t('my_data_apps_granted_scopes') }}</p>
                            <div class="flex flex-wrap gap-1">
                                <span v-for="scope in app.scopes" :key="scope" class="inline-block text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                    {{ scopeLabel(scope) }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between">
                            <span v-if="app.consentedAt" class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $t('my_data_apps_consent_date') }}: {{ new Date(app.consentedAt).toLocaleDateString() }}
                            </span>
                            <button
                                @click="revokeApp(app.clientId)"
                                class="text-xs text-red-600 hover:text-red-500 dark:text-red-400 dark:hover:text-red-300"
                            >
                                {{ $t('my_data_apps_revoke') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Export -->
        <div class="grid md:grid-cols-3 gap-6 md:gap-10 border-t border-gray-200/50 dark:border-gray-700/50 pt-8">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $t('my_data_export_title') }}</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $t('my_data_export_description') }}</p>
            </div>
            <div class="md:col-span-2">
                <a :href="route('my-data.export')" download class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                    {{ $t('my_data_export_button') }}
                </a>
            </div>
        </div>

        <!-- Account Deletion -->
        <div class="grid md:grid-cols-3 gap-6 md:gap-10 border-t border-red-200/50 dark:border-red-900/30 pt-8">
            <div>
                <h3 class="text-sm font-semibold text-red-600 dark:text-red-400">{{ $t('my_data_delete_title') }}</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $t('my_data_delete_description') }}</p>
            </div>
            <div class="md:col-span-2">
                <button
                    @click="confirmDeleteAccount"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 bg-white dark:bg-gray-800 hover:bg-red-50 dark:hover:bg-red-950/30"
                >
                    {{ $t('my_data_delete_button') }}
                </button>
                <p v-if="deleteForm.errors.delete" class="mt-2 text-sm text-red-600 dark:text-red-400">
                    {{ deleteForm.errors.delete }}
                </p>
            </div>
        </div>
    </div>

    <!-- Revoke Confirmation Dialog -->
    <div v-if="showRevokeDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showRevokeDialog = false">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md mx-4 shadow-xl">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $t('my_data_apps_revoke') }}</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $t('my_data_apps_revoke_confirm') }}</p>
            <div class="mt-4 flex justify-end gap-3">
                <button @click="showRevokeDialog = false" class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                    {{ $t('cancel') }}
                </button>
                <button @click="confirmRevoke" class="px-3 py-1.5 text-sm rounded-md bg-red-600 text-white hover:bg-red-500">
                    {{ $t('my_data_apps_revoke') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Account Confirmation Dialog -->
    <div v-if="showDeleteDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showDeleteDialog = false">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md mx-4 shadow-xl">
            <h3 class="text-sm font-semibold text-red-600 dark:text-red-400">{{ $t('my_data_delete_title') }}</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $t('my_data_delete_confirm') }}</p>
            <div class="mt-4 flex justify-end gap-3">
                <button @click="showDeleteDialog = false" class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                    {{ $t('cancel') }}
                </button>
                <button @click="submitDelete" :disabled="deleteForm.processing" class="px-3 py-1.5 text-sm rounded-md bg-red-600 text-white hover:bg-red-500 disabled:opacity-50">
                    {{ $t('my_data_delete_button') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DataField from './MyData/DataField.vue'

const props = defineProps({
    profile: Object,
    isStaff: Boolean,
    groups: Array,
    conventions: Array,
    visibility: Object,
    activityLog: Object,
    connectedApps: Array,
})

const scopeLabels = {
    openid: 'my_data_scope_openid',
    profile: 'my_data_scope_profile',
    email: 'my_data_scope_email',
    phone: 'my_data_scope_phone',
    address: 'my_data_scope_address',
    groups: 'my_data_scope_groups',
    offline_access: 'my_data_scope_offline_access',
}

function scopeLabel(scope) {
    return scopeLabels[scope] ? trans(scopeLabels[scope]) : scope
}

// Revoke app consent
const showRevokeDialog = ref(false)
const revokeClientId = ref(null)

function revokeApp(clientId) {
    revokeClientId.value = clientId
    showRevokeDialog.value = true
}

function confirmRevoke() {
    router.delete(route('my-data.revoke-app', revokeClientId.value), {
        preserveScroll: true,
        onFinish: () => {
            showRevokeDialog.value = false
            revokeClientId.value = null
        },
    })
}

// Delete account
const showDeleteDialog = ref(false)
const deleteForm = useForm('delete', route('my-data.delete-account'), {})

function confirmDeleteAccount() {
    showDeleteDialog.value = true
}

function submitDelete() {
    deleteForm.submit({
        onFinish: () => {
            showDeleteDialog.value = false
        },
    })
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'
export default {
    layout: AccountLayout,
}
</script>
