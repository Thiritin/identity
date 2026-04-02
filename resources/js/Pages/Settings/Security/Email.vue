<template>
    <Head :title="$t('security_email')" />
    <div>
        <Link :href="route('settings.security')" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-4">
            <ArrowLeft class="h-4 w-4" />
            {{ $t('security') }}
        </Link>

        <SettingsHeader>{{ $t('security_email') }}</SettingsHeader>
        <SettingsSubHeader class="mb-4">
            {{ currentEmail }}
        </SettingsSubHeader>

        <form @submit.prevent="submitEmail" class="space-y-4">
            <div class="flex flex-col gap-2">
                <label for="email">{{ $t('security_email_new') }}</label>
                <Input id="email" type="email" autocomplete="email"
                       v-model="form.email"
                       :class="{ 'border-destructive': form.errors.email }" />
                <p v-if="form.errors.email" class="text-sm text-destructive">
                    {{ form.errors.email }}
                </p>
            </div>
            <div class="flex justify-end">
                <Button type="submit" :disabled="form.processing">{{ $t('security_email_save') }}</Button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import SettingsHeader from '@/Components/Settings/SettingsHeader.vue'
import SettingsSubHeader from '@/Components/Settings/SettingsSubHeader.vue'

defineProps({
    currentEmail: String,
})

const form = useForm('post', route('settings.security.email.store'), {
    email: '',
})

function submitEmail() {
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
