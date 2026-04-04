<template>
    <Head :title="$t('staff_totp_required_title')" />
    <div class="text-center">
        <Logo class="mx-auto" />
        <LoginScreenWelcome
            :title="$t('staff_totp_required_title')"
            :sub-title="$t('staff_totp_required_subtitle')"
            class="mb-6"
        />
    </div>
    <p class="text-center text-sm text-gray-600 dark:text-primary-400">
        {{ $t('staff_totp_required_description') }}
    </p>
    <div class="mt-8">
        <a
            :href="portalLoginUrl"
            class="flex w-full items-center justify-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90"
        >
            {{ $t('staff_totp_required_button') }}
        </a>
    </div>
</template>
<script setup>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head } from '@inertiajs/vue3'

defineOptions({ layout: AuthLayout })

defineProps({
    portalLoginUrl: String,
})
</script>
