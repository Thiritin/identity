<template>
    <Head :title="$t('security_authenticator_app')" />
    <div>
        <Link :href="route('settings.security')" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-4">
            <ArrowLeft class="h-4 w-4" />
            {{ $t('security') }}
        </Link>

        <SettingsHeader>{{ $t('security_authenticator_app') }}</SettingsHeader>
        <SettingsSubHeader class="mb-4">
            <template v-if="totpEnabled">
                {{ $t('security_authenticator_enabled') }}<span v-if="totpLastUsed"> &middot; {{ $t('security_authenticator_last_used', { date: totpLastUsed }) }}</span>
            </template>
            <template v-else>{{ $t('totp_protect_account') }}</template>
        </SettingsSubHeader>

        <!-- Setup flow (when disabled) -->
        <div v-if="!totpEnabled">
            <!-- Step 1: Install an App -->
            <div v-if="currentStep === 1">
                <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ $t('totp_step1_explainer') }}
                </p>
                <p class="mb-6 text-xs text-gray-500 dark:text-gray-400">
                    {{ $t('totp_step1_compatible_note') }}
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                    <div class="flex flex-col items-center rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <img src="/images/google-auth-appstore.svg"
                             alt="QR code to download Google Authenticator on the App Store"
                             class="h-40 w-40 mb-3" />
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $t('totp_step1_scan_iphone') }}
                        </p>
                    </div>
                    <div class="flex flex-col items-center rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <img src="/images/google-auth-playstore.svg"
                             alt="QR code to download Google Authenticator on the Play Store"
                             class="h-40 w-40 mb-3" />
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $t('totp_step1_scan_android') }}
                        </p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button @click="goToStep2" :disabled="loadingTotp">
                        {{ loadingTotp ? $t('totp_loading') : $t('totp_step1_next') }}
                    </Button>
                </div>
            </div>

            <!-- Step 2: Scan the Code -->
            <div v-if="currentStep === 2">
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ $t('totp_step2_instruction') }}
                </p>
                <img class="mx-auto bg-white mb-2" :src="totpSetupData.qr_code" alt="QR Code" />
                <p class="text-center text-xs text-gray-500 mb-6">{{ totpSetupData.secret }}</p>
                <div class="flex justify-between">
                    <Button variant="ghost" @click="currentStep = 1">
                        <ArrowLeft class="h-4 w-4 mr-1" />
                        {{ $t('totp_back') }}
                    </Button>
                    <Button @click="currentStep = 3">{{ $t('totp_step2_continue') }}</Button>
                </div>
            </div>

            <!-- Step 3: Verify & Enable -->
            <div v-if="currentStep === 3">
                <form @submit.prevent="submitTotpEnable" class="space-y-4">
                    <div class="flex flex-col gap-2">
                        <label for="totp_code">{{ $t('totp_verification_code') }}</label>
                        <Input id="totp_code" type="text" inputmode="numeric" maxlength="6"
                               v-model="totpEnableForm.code"
                               :class="{ 'border-destructive': totpEnableForm.errors.code }" />
                        <p v-if="totpEnableForm.errors.code" class="text-sm text-destructive">
                            {{ totpEnableForm.errors.code }}
                        </p>
                    </div>
                    <div class="flex justify-between">
                        <Button type="button" variant="ghost" @click="currentStep = 2">
                            <ArrowLeft class="h-4 w-4 mr-1" />
                            {{ $t('totp_back') }}
                        </Button>
                        <Button type="submit" :disabled="totpEnableForm.processing">{{ $t('totp_verify_enable') }}</Button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Disable flow (when enabled) -->
        <div v-else>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                {{ $t('totp_disable_instructions') }}
            </p>
            <form @submit.prevent="submitTotpDisable" class="space-y-4">
                <div class="flex flex-col gap-2">
                    <label for="totp_disable_password">{{ $t('password') }}</label>
                    <Input id="totp_disable_password" type="password"
                           v-model="totpDisableForm.password"
                           :class="{ 'border-destructive': totpDisableForm.errors.password }" />
                    <p v-if="totpDisableForm.errors.password" class="text-sm text-destructive">
                        {{ totpDisableForm.errors.password }}
                    </p>
                </div>
                <div class="flex justify-end">
                    <Button variant="destructive" type="submit" :disabled="totpDisableForm.processing">
                        {{ $t('totp_disable') }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import SettingsHeader from '@/Components/Settings/SettingsHeader.vue'
import SettingsSubHeader from '@/Components/Settings/SettingsSubHeader.vue'

defineProps({
    totpEnabled: Boolean,
    totpLastUsed: String,
})

const currentStep = ref(1)
const totpSetupData = ref(null)
const loadingTotp = ref(false)

const totpEnableForm = useForm('post', route('settings.two-factor.totp.store'), {
    code: '',
    secret: '',
})

const totpDisableForm = useForm('delete', route('settings.two-factor.totp.destroy'), {
    password: '',
})

async function goToStep2() {
    if (totpSetupData.value) {
        currentStep.value = 2
        return
    }
    loadingTotp.value = true
    try {
        const response = await fetch(route('settings.two-factor.totp.setup'))
        if (!response.ok) {
            return
        }
        const data = await response.json()
        totpSetupData.value = data
        totpEnableForm.secret = data.secret
        currentStep.value = 2
    } finally {
        loadingTotp.value = false
    }
}

function submitTotpEnable() {
    totpEnableForm.submit({
        preserveScroll: true,
    })
}

function submitTotpDisable() {
    totpDisableForm.submit({
        preserveScroll: true,
    })
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
