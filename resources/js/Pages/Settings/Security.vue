<template>
    <Head :title="$t('security')" />
    <div class="space-y-8">
        <!-- Login Section -->
        <div class="grid md:grid-cols-3 gap-6 md:gap-10">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('security_login_header') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('security_login_subtitle') }}</p>
            </div>
            <div class="md:col-span-2">
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <Link :href="route('settings.security.email')"
                          class="flex items-center justify-between py-4 first:pt-0 group">
                        <div class="flex items-center gap-3">
                            <Mail class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $t('email') }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ usePage().props.user.email }}</p>
                            </div>
                        </div>
                        <ChevronRight class="h-5 w-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
                    </Link>
                    <Link :href="route('settings.security.password')"
                          class="flex items-center justify-between py-4 group">
                        <div class="flex items-center gap-3">
                            <KeyRound class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $t('security_password') }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ passwordChangedAt ? $t('security_password_last_changed', { date: passwordChangedAt }) : $t('security_password_change') }}
                                </p>
                            </div>
                        </div>
                        <ChevronRight class="h-5 w-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
                    </Link>
                    <Link :href="route('settings.security.passkeys')"
                          class="flex items-center justify-between py-4 group">
                        <div class="flex items-center gap-3">
                            <Fingerprint class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $t('security_passkeys') }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ passkeyCount > 0 ? $t('security_passkeys_count', { count: passkeyCount }) : $t('security_passkeys_none') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Badge :variant="passkeyCount > 0 ? 'default' : 'secondary'">
                                {{ passkeyCount > 0 ? $t('security_enabled') : $t('security_disabled') }}
                            </Badge>
                            <ChevronRight class="h-5 w-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Two-Factor Section -->
        <div class="grid md:grid-cols-3 gap-6 md:gap-10 border-t border-gray-200/50 dark:border-primary-800/50 pt-8">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('security_2fa_header') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('security_2fa_subtitle') }}</p>
            </div>
            <div class="md:col-span-2">
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <Link :href="route('settings.security.totp')"
                          class="flex items-center justify-between py-4 first:pt-0 group">
                        <div class="flex items-center gap-3">
                            <Smartphone class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $t('security_authenticator_app') }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    <template v-if="totpEnabled">
                                        {{ $t('security_authenticator_enabled') }}<span v-if="totpLastUsed"> &middot; {{ $t('security_authenticator_last_used', { date: totpLastUsed }) }}</span>
                                    </template>
                                    <template v-else>{{ $t('security_authenticator_not_configured') }}</template>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Badge :variant="totpEnabled ? 'default' : 'secondary'">
                                {{ totpEnabled ? $t('security_enabled') : $t('security_disabled') }}
                            </Badge>
                            <ChevronRight class="h-5 w-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
                        </div>
                    </Link>
                    <Link :href="route('settings.security.yubikey')"
                          class="flex items-center justify-between py-4 group">
                        <div class="flex items-center gap-3">
                            <Usb class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $t('yubikey_otp') }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ yubikeyCount > 0 ? $t('security_yubikey_keys_registered', { count: yubikeyCount }) : $t('security_yubikey_no_keys') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Badge :variant="yubikeyCount > 0 ? 'default' : 'secondary'">
                                {{ yubikeyCount > 0 ? $t('security_enabled') : $t('security_disabled') }}
                            </Badge>
                            <ChevronRight class="h-5 w-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
                        </div>
                    </Link>
                    <Link v-if="backupCodesEnabled" :href="route('settings.security.backup-codes')"
                          class="flex items-center justify-between py-4 group">
                        <div class="flex items-center gap-3">
                            <FileKey2 class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $t('security_backup_codes') }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('security_backup_codes_remaining', { count: backupCodesCount }) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Badge :variant="backupCodesCount > 0 ? 'default' : 'destructive'">
                                {{ backupCodesCount > 0 ? $t('security_backup_codes_count', { count: backupCodesCount }) : $t('security_backup_codes_none') }}
                            </Badge>
                            <ChevronRight class="h-5 w-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
                        </div>
                    </Link>
                    <div v-else class="flex items-center justify-between py-4 opacity-50">
                        <div class="flex items-center gap-3">
                            <FileKey2 class="h-5 w-5 text-gray-400" />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $t('security_backup_codes') }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('security_backup_codes_add_primary') }}</p>
                            </div>
                        </div>
                    </div>
                    <Link :href="route('settings.security.security-keys')"
                          class="flex items-center justify-between py-4 group">
                        <div class="flex items-center gap-3">
                            <ShieldCheck class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $t('security_security_keys') }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ securityKeyCount > 0 ? $t('security_security_keys_count', { count: securityKeyCount }) : $t('security_security_keys_none') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Badge :variant="securityKeyCount > 0 ? 'default' : 'secondary'">
                                {{ securityKeyCount > 0 ? $t('security_enabled') : $t('security_disabled') }}
                            </Badge>
                            <ChevronRight class="h-5 w-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Sessions Section -->
        <div class="grid md:grid-cols-3 gap-6 md:gap-10 border-t border-gray-200/50 dark:border-primary-800/50 pt-8">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('security_sessions_header') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('security_sessions_subtitle') }}</p>
            </div>
            <div class="md:col-span-2">
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <Link :href="route('settings.security.sessions')"
                          class="flex items-center justify-between py-4 first:pt-0 group">
                        <div class="flex items-center gap-3">
                            <Monitor class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $t('security_sessions_header') }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('security_sessions_active_count', { count: sessionCount }) }}
                                </p>
                            </div>
                        </div>
                        <ChevronRight class="h-5 w-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { ChevronRight, KeyRound, Fingerprint, Smartphone, Usb, ShieldCheck, Mail, FileKey2, Monitor } from 'lucide-vue-next'
import { Badge } from '@/Components/ui/badge'

defineProps({
    passwordChangedAt: String,
    totpEnabled: Boolean,
    totpLastUsed: String,
    yubikeyCount: Number,
    backupCodesEnabled: Boolean,
    backupCodesCount: Number,
    passkeyCount: Number,
    securityKeyCount: Number,
    sessionCount: Number,
})
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
