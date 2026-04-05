<template>
    <Head :title="$t('my_data_title')" />

    <div class="space-y-8">
        <!-- Page header -->
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('my_data_title') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('my_data_subtitle') }}</p>
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
                <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="app in connectedApps" :key="app.clientId">
                        <button
                            @click="toggleApp(app.clientId)"
                            class="w-full flex items-center gap-3 py-3 text-left group"
                        >
                            <img v-if="app.image" :src="app.image" :alt="app.name" class="h-10 w-10 rounded-lg border border-gray-200 dark:border-gray-700 object-cover" />
                            <div v-else class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800 text-lg font-semibold text-gray-400 dark:text-gray-500">
                                {{ app.name?.charAt(0)?.toUpperCase() }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ app.name }}</p>
                                <p v-if="app.description" class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ app.description }}</p>
                            </div>
                            <ChevronDown class="h-4 w-4 shrink-0 text-gray-400 transition-transform" :class="{ 'rotate-180': expandedApps.has(app.clientId) }" />
                        </button>
                        <div v-if="expandedApps.has(app.clientId)" class="pb-4 pl-13 space-y-3">
                            <div class="space-y-1">
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ $t('my_data_apps_granted_scopes') }}</p>
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="scope in app.scopes" :key="scope" class="inline-block text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                        {{ scopeLabel(scope) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="space-y-1">
                                    <span v-if="app.consentedAt" class="block text-xs text-gray-500 dark:text-gray-400">
                                        {{ $t('my_data_apps_consent_date') }}: {{ new Date(app.consentedAt).toLocaleDateString() }}
                                    </span>
                                    <a v-if="app.policyUri" :href="app.policyUri" target="_blank" class="block text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ $t('my_data_apps_privacy_policy') }}
                                    </a>
                                </div>
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

        <!-- Staff Profile Consent -->
        <div v-if="$page.props.staffProfileConsent?.is_staff" class="grid md:grid-cols-3 gap-6 md:gap-10 border-t border-gray-200/50 dark:border-gray-700/50 pt-8">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $t('staff_profile_consent_state_heading') }}</h3>
            </div>
            <div class="md:col-span-2 space-y-3">
                <template v-if="$page.props.staffProfileConsent.granted">
                    <p class="text-sm">
                        {{ $t('staff_profile_consent_state_granted', {
                            date: new Date($page.props.staffProfileConsent.granted_at).toLocaleDateString(),
                            version: $page.props.staffProfileConsent.version,
                        }) }}
                    </p>
                    <p v-if="!$page.props.staffProfileConsent.is_current" class="text-sm text-amber-600 dark:text-amber-400">
                        {{ $t('staff_profile_consent_notice_updated') }}
                    </p>
                    <Button variant="destructive" size="sm" @click="showConsentWithdrawDialog = true">
                        {{ $t('staff_profile_consent_withdraw_button') }}
                    </Button>
                </template>
                <template v-else>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $t('staff_profile_consent_state_not_granted') }}</p>
                    <Link :href="route('settings.profile')" class="text-sm underline">{{ $t('staff_profile_consent_reminder_expand') }}</Link>
                </template>
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
                <p v-if="usePage().props.errors?.delete" class="mt-2 text-sm text-red-600 dark:text-red-400">
                    {{ usePage().props.errors.delete }}
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

    <!-- Withdraw Staff Profile Consent Dialog -->
    <Dialog v-model:open="showConsentWithdrawDialog">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ $t('staff_profile_consent_withdraw_confirm_heading') }}</DialogTitle>
            </DialogHeader>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $t('staff_profile_consent_withdraw_confirm_body') }}</p>
            <DialogFooter>
                <Button variant="outline" @click="showConsentWithdrawDialog = false">{{ $t('cancel') }}</Button>
                <Button variant="destructive" @click="withdrawStaffProfileConsent">{{ $t('staff_profile_consent_withdraw_button') }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Delete Account Confirmation Dialog -->
    <div v-if="showDeleteDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showDeleteDialog = false">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md mx-4 shadow-xl">
            <h3 class="text-sm font-semibold text-red-600 dark:text-red-400">{{ $t('my_data_delete_title') }}</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $t('my_data_delete_confirm') }}</p>
            <div class="mt-4 flex justify-end gap-3">
                <button @click="showDeleteDialog = false" class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                    {{ $t('cancel') }}
                </button>
                <button @click="submitDelete" class="px-3 py-1.5 text-sm rounded-md bg-red-600 text-white hover:bg-red-500">
                    {{ $t('my_data_delete_button') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { ChevronDown } from 'lucide-vue-next'
import { Button } from '@/Components/ui/button'
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog'

const props = defineProps({
    isStaff: Boolean,
    groups: Array,
    conventions: Array,
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

// Expand/collapse app details
const expandedApps = ref(new Set())

function toggleApp(clientId) {
    if (expandedApps.value.has(clientId)) {
        expandedApps.value.delete(clientId)
    } else {
        expandedApps.value.add(clientId)
    }
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

// Withdraw staff profile consent
const showConsentWithdrawDialog = ref(false)

function withdrawStaffProfileConsent() {
    router.delete(route('settings.staff-profile.consent.withdraw'), {
        onSuccess: () => { showConsentWithdrawDialog.value = false },
        preserveScroll: true,
    })
}

// Delete account
const showDeleteDialog = ref(false)

function confirmDeleteAccount() {
    showDeleteDialog.value = true
}

function submitDelete() {
    router.delete(route('my-data.delete-account'), {
        onSuccess: () => {
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
