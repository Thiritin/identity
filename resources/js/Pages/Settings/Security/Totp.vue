<template>
    <Head title="Authenticator App" />
    <div>
        <Link :href="route('settings.security')" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-4">
            <ArrowLeft class="h-4 w-4" />
            Security
        </Link>

        <SettingsHeader>Authenticator App</SettingsHeader>
        <SettingsSubHeader class="mb-4">
            <template v-if="totpEnabled">
                Enabled<span v-if="totpLastUsed"> &middot; Last used {{ totpLastUsed }}</span>
            </template>
            <template v-else>Protect your account with an authenticator app</template>
        </SettingsSubHeader>

        <!-- Setup flow (when disabled) -->
        <div v-if="!totpEnabled" class="max-w-md">
            <div v-if="totpSetupData">
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    Scan the QR code with your authenticator app, then enter the code below.
                </p>
                <img class="mx-auto bg-white mb-2" :src="totpSetupData.qr_code" alt="QR Code" />
                <p class="text-center text-xs text-gray-500 mb-4">{{ totpSetupData.secret }}</p>
                <form @submit.prevent="submitTotpEnable" class="space-y-4">
                    <div class="flex flex-col gap-2">
                        <label for="totp_code">Verification code</label>
                        <Input id="totp_code" type="text" inputmode="numeric" maxlength="6"
                               v-model="totpEnableForm.code"
                               :class="{ 'border-destructive': totpEnableForm.errors.code }" />
                        <p v-if="totpEnableForm.errors.code" class="text-sm text-destructive">
                            {{ totpEnableForm.errors.code }}
                        </p>
                    </div>
                    <div class="flex justify-end">
                        <Button type="submit" :disabled="totpEnableForm.processing">Verify &amp; Enable</Button>
                    </div>
                </form>
            </div>
            <div v-else>
                <Button variant="outline" @click="fetchTotpSetup" :disabled="loadingTotp">
                    {{ loadingTotp ? 'Loading...' : 'Start setup' }}
                </Button>
            </div>
        </div>

        <!-- Disable flow (when enabled) -->
        <div v-else class="max-w-md">
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                Enter your password to disable the authenticator app.
            </p>
            <form @submit.prevent="submitTotpDisable" class="space-y-4">
                <div class="flex flex-col gap-2">
                    <label for="totp_disable_password">Password</label>
                    <Input id="totp_disable_password" type="password"
                           v-model="totpDisableForm.password"
                           :class="{ 'border-destructive': totpDisableForm.errors.password }" />
                    <p v-if="totpDisableForm.errors.password" class="text-sm text-destructive">
                        {{ totpDisableForm.errors.password }}
                    </p>
                </div>
                <div class="flex justify-end">
                    <Button variant="destructive" type="submit" :disabled="totpDisableForm.processing">
                        Disable
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

const totpSetupData = ref(null)
const loadingTotp = ref(false)

const totpEnableForm = useForm('post', route('settings.two-factor.totp.store'), {
    code: '',
    secret: '',
})

const totpDisableForm = useForm('delete', route('settings.two-factor.totp.destroy'), {
    password: '',
})

async function fetchTotpSetup() {
    loadingTotp.value = true
    try {
        const response = await fetch(route('settings.two-factor.totp.setup'))
        const data = await response.json()
        totpSetupData.value = data
        totpEnableForm.secret = data.secret
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
