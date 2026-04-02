<template>
    <Head title="Security" />
    <div>
        <!-- Login Section -->
        <SettingsHeader>Login</SettingsHeader>
        <SettingsSubHeader class="mb-3">How you sign in to your account</SettingsSubHeader>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <Link :href="route('settings.security.password')"
                  class="flex items-center justify-between py-4 group">
                <div class="flex items-center gap-3">
                    <KeyRound class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">Password</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ passwordChangedAt ? `Last changed ${passwordChangedAt}` : 'Change your password' }}
                        </p>
                    </div>
                </div>
                <ChevronRight class="h-5 w-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            </Link>
            <div class="flex items-center justify-between py-4 opacity-50">
                <div class="flex items-center gap-3">
                    <Fingerprint class="h-5 w-5 text-gray-400" />
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">Passkeys</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Coming soon</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Two-Factor Section -->
        <SettingsHeader class="mt-8">Two-Factor Authentication</SettingsHeader>
        <SettingsSubHeader class="mb-3">Extra security for your account</SettingsSubHeader>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <Link :href="route('settings.security.totp')"
                  class="flex items-center justify-between py-4 group">
                <div class="flex items-center gap-3">
                    <Smartphone class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">Authenticator App</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <template v-if="totpEnabled">
                                Enabled<span v-if="totpLastUsed"> &middot; Last used {{ totpLastUsed }}</span>
                            </template>
                            <template v-else>Not configured</template>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Badge :variant="totpEnabled ? 'default' : 'secondary'">
                        {{ totpEnabled ? 'Enabled' : 'Disabled' }}
                    </Badge>
                    <ChevronRight class="h-5 w-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
                </div>
            </Link>
            <Link :href="route('settings.security.yubikey')"
                  class="flex items-center justify-between py-4 group">
                <div class="flex items-center gap-3">
                    <Usb class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">Yubikey OTP</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ yubikeyCount > 0 ? `${yubikeyCount} key(s) registered` : 'No keys registered' }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Badge :variant="yubikeyCount > 0 ? 'default' : 'secondary'">
                        {{ yubikeyCount > 0 ? 'Enabled' : 'Disabled' }}
                    </Badge>
                    <ChevronRight class="h-5 w-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
                </div>
            </Link>
            <div class="flex items-center justify-between py-4 opacity-50">
                <div class="flex items-center gap-3">
                    <ShieldCheck class="h-5 w-5 text-gray-400" />
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">Security Keys</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Coming soon</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ChevronRight, KeyRound, Fingerprint, Smartphone, Usb, ShieldCheck } from 'lucide-vue-next'
import { Badge } from '@/Components/ui/badge'
import SettingsHeader from '@/Components/Settings/SettingsHeader.vue'
import SettingsSubHeader from '@/Components/Settings/SettingsSubHeader.vue'

defineProps({
    passwordChangedAt: String,
    totpEnabled: Boolean,
    totpLastUsed: String,
    yubikeyCount: Number,
})
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
