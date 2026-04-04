<template>
    <Head :title="$t('security_security_keys')" />
    <div>
        <Link :href="route('settings.security')" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-4">
            <ArrowLeft class="h-4 w-4" />
            {{ $t('security') }}
        </Link>

        <SettingsHeader>{{ $t('security_security_keys') }}</SettingsHeader>
        <SettingsSubHeader class="mb-4">
            {{ securityKeys.length > 0 ? $t('security_key_keys_registered', { count: securityKeys.length }) : $t('security_key_register_subtitle') }}
        </SettingsSubHeader>

        <!-- Registered security keys list -->
        <div v-if="securityKeys.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700 mb-6">
            <div v-for="key in securityKeys" :key="key.id"
                 class="flex items-center justify-between py-3">
                <div>
                    <p class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ key.name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ key.last_used_at ? $t('security_key_last_used', { date: key.last_used_at }) : $t('security_key_never_used') }}
                    </p>
                </div>
                <Button variant="ghost" size="sm" @click="startRemove(key.id)">
                    <Trash2 class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <!-- Remove key confirmation -->
        <div v-if="removeId" class="mb-6 rounded-lg border border-destructive/50 p-4">
            <form @submit.prevent="submitRemove" class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $t('security_key_remove_password') }}</p>
                <div class="flex flex-col gap-2">
                    <label for="security_key_remove_password">{{ $t('password') }}</label>
                    <Input id="security_key_remove_password" type="password"
                           v-model="removeForm.password"
                           :class="{ 'border-destructive': removeForm.errors.password }" />
                    <p v-if="removeForm.errors.password" class="text-sm text-destructive">
                        {{ removeForm.errors.password }}
                    </p>
                </div>
                <div class="flex justify-end gap-3">
                    <Button variant="destructive" size="sm" type="submit" :disabled="removeForm.processing" class="order-2">
                        {{ $t('security_key_remove') }}
                    </Button>
                    <Button type="button" variant="secondary" size="sm" @click="removeId = null" class="order-1">{{ $t('cancel') }}</Button>
                </div>
            </form>
        </div>

        <!-- Add new security key -->
        <div v-if="!showAddForm">
            <Button variant="outline" @click="startRegistrationFlow">{{ $t('security_key_add_new') }}</Button>
        </div>
        <div v-else>
            <form @submit.prevent="submitAdd" class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $t('security_key_tap_to_register') }}</p>
                <div class="flex flex-col gap-2">
                    <label for="security_key_name">{{ $t('name') }}</label>
                    <Input id="security_key_name" type="text" placeholder="YubiKey, Titan, etc."
                           v-model="addForm.name"
                           :class="{ 'border-destructive': addForm.errors.name }" />
                    <p v-if="addForm.errors.name" class="text-sm text-destructive">
                        {{ addForm.errors.name }}
                    </p>
                </div>
                <p v-if="addForm.errors.credential" class="text-sm text-destructive">
                    {{ addForm.errors.credential }}
                </p>
                <p v-if="registrationError" class="text-sm text-destructive">
                    {{ registrationError }}
                </p>
                <div class="flex justify-end gap-3">
                    <Button type="submit" :disabled="addForm.processing || !registrationResponse" class="order-2">{{ $t('security_key_register_key') }}</Button>
                    <Button type="button" variant="secondary" size="sm" @click="cancelAdd" class="order-1">{{ $t('cancel') }}</Button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Trash2 } from 'lucide-vue-next'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import SettingsHeader from '@/Components/Settings/SettingsHeader.vue'
import SettingsSubHeader from '@/Components/Settings/SettingsSubHeader.vue'
import { startRegistration } from '@simplewebauthn/browser'
import { trans } from 'laravel-vue-i18n'

defineProps({
    securityKeys: Array,
})

const showAddForm = ref(false)
const removeId = ref(null)
const registrationResponse = ref(null)
const registrationError = ref(null)

const addForm = useForm('post', route('settings.two-factor.security-key.store'), {
    credential: '',
    name: '',
})

const removeForm = useForm('delete', route('settings.two-factor.security-key.destroy'), {
    keyId: '',
    password: '',
})

async function startRegistrationFlow() {
    registrationError.value = null
    registrationResponse.value = null
    showAddForm.value = true

    try {
        const optionsRes = await fetch(route('settings.two-factor.security-key.options'))
        const optionsJSON = await optionsRes.json()
        const result = await startRegistration({ optionsJSON })
        registrationResponse.value = result
        addForm.credential = JSON.stringify(result)
    } catch (e) {
        if (e.name === 'NotAllowedError') {
            registrationError.value = trans('registration_cancelled')
        } else {
            registrationError.value = e.message
        }
    }
}

function submitAdd() {
    addForm.submit({
        preserveScroll: true,
        onSuccess: () => {
            showAddForm.value = false
            registrationResponse.value = null
            addForm.reset()
        },
    })
}

function cancelAdd() {
    showAddForm.value = false
    registrationResponse.value = null
    registrationError.value = null
    addForm.reset()
}

function startRemove(keyId) {
    removeId.value = keyId
    removeForm.keyId = keyId
}

function submitRemove() {
    removeForm.submit({
        preserveScroll: true,
        onSuccess: () => {
            removeId.value = null
            removeForm.reset()
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
