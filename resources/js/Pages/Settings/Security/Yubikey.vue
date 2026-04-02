<template>
    <Head :title="$t('yubikey_otp')" />
    <div>
        <Link :href="route('settings.security')" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-4">
            <ArrowLeft class="h-4 w-4" />
            {{ $t('security') }}
        </Link>

        <SettingsHeader>{{ $t('yubikey_otp') }}</SettingsHeader>
        <SettingsSubHeader class="mb-4">
            {{ yubikeys.length > 0 ? $t('security_yubikey_keys_registered', { count: yubikeys.length }) : $t('yubikey_register_subtitle') }}
        </SettingsSubHeader>

        <!-- Registered keys list -->
        <div v-if="yubikeys.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700 mb-6">
            <div v-for="key in yubikeys" :key="key.id"
                 class="flex items-center justify-between py-3">
                <div>
                    <p class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ key.name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ key.last_used_at ? $t('yubikey_last_used', { date: key.last_used_at }) : $t('yubikey_never_used') }}
                    </p>
                </div>
                <Button variant="ghost" size="sm" @click="startYubikeyRemove(key.id)">
                    <Trash2 class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <!-- Remove key confirmation -->
        <div v-if="yubikeyRemoveId" class="mb-6 rounded-lg border border-destructive/50 p-4">
            <form @submit.prevent="submitYubikeyRemove" class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $t('yubikey_remove_password') }}</p>
                <div class="flex flex-col gap-2">
                    <label for="yubikey_remove_password">{{ $t('password') }}</label>
                    <Input id="yubikey_remove_password" type="password"
                           v-model="yubikeyRemoveForm.password"
                           :class="{ 'border-destructive': yubikeyRemoveForm.errors.password }" />
                    <p v-if="yubikeyRemoveForm.errors.password" class="text-sm text-destructive">
                        {{ yubikeyRemoveForm.errors.password }}
                    </p>
                </div>
                <div class="flex justify-end gap-3">
                    <Button variant="destructive" size="sm" type="submit" :disabled="yubikeyRemoveForm.processing" class="order-2">
                        {{ $t('yubikey_remove') }}
                    </Button>
                    <Button type="button" variant="secondary" size="sm" @click="yubikeyRemoveId = null" class="order-1">{{ $t('cancel') }}</Button>
                </div>
            </form>
        </div>

        <!-- Add new key -->
        <div v-if="!showAddForm">
            <Button variant="outline" @click="showAddForm = true">{{ $t('yubikey_add_new') }}</Button>
        </div>
        <div v-else>
            <form @submit.prevent="submitYubikeyAdd" class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $t('yubikey_tap_to_register') }}</p>
                <div class="flex flex-col gap-2">
                    <label for="yubikey_code">{{ $t('yubikey_otp') }}</label>
                    <Input id="yubikey_code" type="text" v-model="yubikeyAddForm.code"
                           :class="{ 'border-destructive': yubikeyAddForm.errors.code }" />
                    <p v-if="yubikeyAddForm.errors.code" class="text-sm text-destructive">
                        {{ yubikeyAddForm.errors.code }}
                    </p>
                </div>
                <div class="flex flex-col gap-2">
                    <label for="yubikey_name">{{ $t('name') }}</label>
                    <Input id="yubikey_name" type="text" placeholder="Work, Home, etc."
                           v-model="yubikeyAddForm.name"
                           :class="{ 'border-destructive': yubikeyAddForm.errors.name }" />
                    <p v-if="yubikeyAddForm.errors.name" class="text-sm text-destructive">
                        {{ yubikeyAddForm.errors.name }}
                    </p>
                </div>
                <div class="flex justify-end gap-3">
                    <Button type="submit" :disabled="yubikeyAddForm.processing" class="order-2">{{ $t('yubikey_register_key') }}</Button>
                    <Button type="button" variant="secondary" size="sm" @click="showAddForm = false" class="order-1">{{ $t('cancel') }}</Button>
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

defineProps({
    yubikeys: Array,
})

const showAddForm = ref(false)
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
            showAddForm.value = false
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
