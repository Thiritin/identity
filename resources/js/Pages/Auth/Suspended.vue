<template>
    <Head :title="$t('suspended_title')" />
    <div class="text-center">
        <Logo class="mx-auto" />
        <LoginScreenWelcome
            :title="$t('suspended_title')"
            :sub-title="$t('suspended_subtitle')"
            class="mb-6"
        />
    </div>
    <p class="text-center text-sm text-gray-600 dark:text-primary-400">
        {{ $t('suspended_description') }}
    </p>
    <p class="mt-4 text-center text-sm text-gray-500 dark:text-primary-400">
        <a href="mailto:identity@eurofurence.org" class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-300 dark:hover:text-primary-200">
            identity@eurofurence.org
        </a>
    </p>
</template>
<script setup>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head } from '@inertiajs/vue3'

defineOptions({ layout: AuthLayout })
</script>
