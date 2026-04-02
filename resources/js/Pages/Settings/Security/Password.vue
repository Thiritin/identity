<template>
    <Head :title="$t('security_password')" />
    <div>
        <Link :href="route('settings.security')" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-4">
            <ArrowLeft class="h-4 w-4" />
            {{ $t('security') }}
        </Link>

        <SettingsHeader>{{ $t('security_password') }}</SettingsHeader>
        <SettingsSubHeader class="mb-4">
            {{ passwordChangedAt ? $t('security_password_last_changed', { date: passwordChangedAt }) : $t('security_password_change_account') }}
        </SettingsSubHeader>

        <form @submit.prevent="submitPassword" class="space-y-4">
            <div class="flex flex-col gap-2">
                <label for="current_password">{{ $t('security_password_current') }}</label>
                <Input id="current_password" type="password" autocomplete="current-password"
                       v-model="form.current_password"
                       :class="{ 'border-destructive': form.errors.current_password }" />
                <p v-if="form.errors.current_password" class="text-sm text-destructive">
                    {{ form.errors.current_password }}
                </p>
            </div>
            <div class="flex flex-col gap-2">
                <label for="password">{{ $t('security_password_new') }}</label>
                <Input id="password" type="password" autocomplete="new-password"
                       v-model="form.password"
                       :class="{ 'border-destructive': form.errors.password }" />
                <p v-if="form.errors.password" class="text-sm text-destructive">
                    {{ form.errors.password }}
                </p>
            </div>
            <PasswordInfoBox :password="form.password" class="mt-2 mb-2" />
            <div class="flex flex-col gap-2">
                <label for="password_confirmation">{{ $t('security_password_confirm') }}</label>
                <Input id="password_confirmation" type="password" autocomplete="new-password"
                       v-model="form.password_confirmation"
                       :class="{ 'border-destructive': form.errors.password_confirmation }" />
                <p v-if="form.errors.password_confirmation" class="text-sm text-destructive">
                    {{ form.errors.password_confirmation }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Checkbox id="destroy_sessions" v-model="form.destroy_sessions" />
                <label for="destroy_sessions" class="text-sm">{{ $t('security_sign_out_sessions') }}</label>
            </div>
            <div class="flex justify-end">
                <Button type="submit" :disabled="form.processing">{{ $t('security_save_password') }}</Button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import { Checkbox } from '@/Components/ui/checkbox'
import SettingsHeader from '@/Components/Settings/SettingsHeader.vue'
import SettingsSubHeader from '@/Components/Settings/SettingsSubHeader.vue'
import PasswordInfoBox from '@/Auth/PasswordInfoBox.vue'

defineProps({
    passwordChangedAt: String,
})

const form = useForm('post', route('settings.update-password.store'), {
    current_password: '',
    password: '',
    password_confirmation: '',
    destroy_sessions: false,
})

function submitPassword() {
    form.submit({
        preserveScroll: true,
        onSuccess: () => form.reset(),
    })
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
