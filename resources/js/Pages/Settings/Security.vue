<template>
    <Head title="Security" />
    <div class="space-y-6">
        <!-- Password Card -->
        <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between p-4">
                <div>
                    <h3 class="font-medium text-gray-900 dark:text-gray-100">Password</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Change your account password</p>
                </div>
                <Button variant="outline" size="sm" @click="showPasswordForm = !showPasswordForm">
                    {{ showPasswordForm ? 'Cancel' : 'Change password' }}
                </Button>
            </div>
            <div v-if="showPasswordForm" class="border-t border-gray-200 dark:border-gray-700 p-4">
                <form @submit.prevent="submitPassword" class="space-y-4 max-w-md">
                    <div class="flex flex-col gap-2">
                        <label for="current_password">Current password</label>
                        <Input id="current_password" type="password" autocomplete="current-password"
                               v-model="passwordForm.current_password"
                               :class="{ 'border-destructive': passwordForm.errors.current_password }" />
                        <p v-if="passwordForm.errors.current_password" class="text-sm text-destructive">
                            {{ passwordForm.errors.current_password }}
                        </p>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="password">New password</label>
                        <Input id="password" type="password" autocomplete="new-password"
                               v-model="passwordForm.password"
                               :class="{ 'border-destructive': passwordForm.errors.password }" />
                        <p v-if="passwordForm.errors.password" class="text-sm text-destructive">
                            {{ passwordForm.errors.password }}
                        </p>
                    </div>
                    <PasswordInfoBox :password="passwordForm.password" class="mt-2 mb-2" />
                    <div class="flex flex-col gap-2">
                        <label for="password_confirmation">Confirm password</label>
                        <Input id="password_confirmation" type="password" autocomplete="new-password"
                               v-model="passwordForm.password_confirmation"
                               :class="{ 'border-destructive': passwordForm.errors.password_confirmation }" />
                        <p v-if="passwordForm.errors.password_confirmation" class="text-sm text-destructive">
                            {{ passwordForm.errors.password_confirmation }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox id="destroy_sessions" v-model="passwordForm.destroy_sessions" />
                        <label for="destroy_sessions" class="text-sm">Sign out of other sessions</label>
                    </div>
                    <div class="flex justify-end">
                        <Button type="submit" :disabled="passwordForm.processing">Save password</Button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Authenticator App Card -->
        <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center gap-3">
                    <div>
                        <h3 class="font-medium text-gray-900 dark:text-gray-100">Authenticator App</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <template v-if="totpEnabled">
                                Enabled <span v-if="totpLastUsed">&middot; Last used {{ totpLastUsed }}</span>
                            </template>
                            <template v-else>Not configured</template>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Badge :variant="totpEnabled ? 'default' : 'secondary'">
                        {{ totpEnabled ? 'Enabled' : 'Disabled' }}
                    </Badge>
                    <Button variant="outline" size="sm" @click="toggleTotpSection">
                        Configure
                    </Button>
                </div>
            </div>
            <div v-if="showTotpSetup" class="border-t border-gray-200 dark:border-gray-700 p-4">
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
                    <div v-else class="flex justify-center py-4">
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
        </div>

        <!-- Security Keys (Yubikey) Card -->
        <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between p-4">
                <div>
                    <h3 class="font-medium text-gray-900 dark:text-gray-100">Security Keys</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ yubikeys.length > 0 ? `${yubikeys.length} key(s) registered` : 'No security keys registered' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Badge :variant="yubikeys.length > 0 ? 'default' : 'secondary'">
                        {{ yubikeys.length > 0 ? 'Enabled' : 'Disabled' }}
                    </Badge>
                    <Button variant="outline" size="sm" @click="showYubikeyAdd = !showYubikeyAdd">
                        Add new
                    </Button>
                </div>
            </div>
            <!-- Registered keys list -->
            <div v-if="yubikeys.length > 0" class="border-t border-gray-200 dark:border-gray-700">
                <div v-for="key in yubikeys" :key="key.id"
                     class="flex items-center justify-between px-4 py-3 ml-4 border-b border-gray-100 dark:border-gray-800 last:border-0">
                    <div>
                        <p class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ key.name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ key.last_used_at ? `Last used ${key.last_used_at}` : 'Never used' }}
                        </p>
                    </div>
                    <Button variant="ghost" size="sm" @click="startYubikeyRemove(key.id)">
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </div>
            <!-- Add new key form -->
            <div v-if="showYubikeyAdd" class="border-t border-gray-200 dark:border-gray-700 p-4">
                <form @submit.prevent="submitYubikeyAdd" class="space-y-4 max-w-md">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Tap your Yubikey to register it.</p>
                    <div class="flex flex-col gap-2">
                        <label for="yubikey_code">Yubikey OTP</label>
                        <Input id="yubikey_code" type="text" v-model="yubikeyAddForm.code"
                               :class="{ 'border-destructive': yubikeyAddForm.errors.code }" />
                        <p v-if="yubikeyAddForm.errors.code" class="text-sm text-destructive">
                            {{ yubikeyAddForm.errors.code }}
                        </p>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="yubikey_name">Name</label>
                        <Input id="yubikey_name" type="text" placeholder="Work, Home, etc."
                               v-model="yubikeyAddForm.name"
                               :class="{ 'border-destructive': yubikeyAddForm.errors.name }" />
                        <p v-if="yubikeyAddForm.errors.name" class="text-sm text-destructive">
                            {{ yubikeyAddForm.errors.name }}
                        </p>
                    </div>
                    <div class="flex justify-end">
                        <Button type="submit" :disabled="yubikeyAddForm.processing">Register key</Button>
                    </div>
                </form>
            </div>
            <!-- Remove key confirmation -->
            <div v-if="yubikeyRemoveId" class="border-t border-gray-200 dark:border-gray-700 p-4">
                <form @submit.prevent="submitYubikeyRemove" class="space-y-4 max-w-md">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Enter your password to remove this key.</p>
                    <div class="flex flex-col gap-2">
                        <label for="yubikey_remove_password">Password</label>
                        <Input id="yubikey_remove_password" type="password"
                               v-model="yubikeyRemoveForm.password"
                               :class="{ 'border-destructive': yubikeyRemoveForm.errors.password }" />
                        <p v-if="yubikeyRemoveForm.errors.password" class="text-sm text-destructive">
                            {{ yubikeyRemoveForm.errors.password }}
                        </p>
                    </div>
                    <div class="flex justify-end gap-3">
                        <Button variant="secondary" size="sm" @click="yubikeyRemoveId = null">Cancel</Button>
                        <Button variant="destructive" size="sm" type="submit" :disabled="yubikeyRemoveForm.processing">
                            Remove
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { Checkbox } from '@/Components/ui/checkbox'
import { Trash2 } from 'lucide-vue-next'
import PasswordInfoBox from '@/Auth/PasswordInfoBox.vue'

const props = defineProps({
    totpEnabled: Boolean,
    totpLastUsed: String,
    yubikeys: Array,
})

// Password
const showPasswordForm = ref(false)
const passwordForm = useForm('post', route('settings.update-password.store'), {
    current_password: '',
    password: '',
    password_confirmation: '',
    destroy_sessions: false,
})

function submitPassword() {
    passwordForm.submit({
        preserveScroll: true,
        onSuccess: () => {
            showPasswordForm.value = false
            passwordForm.reset()
        },
    })
}

// TOTP
const showTotpSetup = ref(false)
const totpSetupData = ref(null)
const loadingTotp = ref(false)

const totpEnableForm = useForm('post', route('settings.two-factor.totp.store'), {
    code: '',
    secret: '',
})

const totpDisableForm = useForm('delete', route('settings.two-factor.totp.destroy'), {
    password: '',
})

function toggleTotpSection() {
    showTotpSetup.value = !showTotpSetup.value
}

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

// Yubikey
const showYubikeyAdd = ref(false)
const yubikeyRemoveId = ref(null)

const yubikeyAddForm = useForm('post', route('settings.two-factor.yubikey.store'), {
    code: '',
    name: '',
})

const yubikeyRemoveForm = useForm('delete', route('settings.two-factor.yubikey.destroy'), {
    keyId: '',
    password: '',
})

function submitYubikeyAdd() {
    yubikeyAddForm.submit({
        preserveScroll: true,
        onSuccess: () => {
            showYubikeyAdd.value = false
            yubikeyAddForm.reset()
        },
    })
}

function startYubikeyRemove(keyId) {
    yubikeyRemoveId.value = keyId
    yubikeyRemoveForm.keyId = keyId
}

function submitYubikeyRemove() {
    yubikeyRemoveForm.submit({
        preserveScroll: true,
        onSuccess: () => {
            yubikeyRemoveId.value = null
            yubikeyRemoveForm.reset()
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
