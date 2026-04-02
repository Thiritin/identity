<template>
    <Head :title="$t('logged_out_title')"></Head>
    <div class="text-center">
        <Logo class="mx-auto"></Logo>
        <LoginScreenWelcome
            :title="$t('logged_out_title')"
            :sub-title="$t('logged_out_subtitle')"
            class="mb-10"
        />
    </div>
    <p class="text-xs text-gray-500 dark:text-gray-400 text-center mb-6">
        {{ $t('logged_out_warning') }}
    </p>
    <div class="text-center">
        <Link
            :href="route('auth.login.view')"
            class="text-sm text-gray-500 hover:text-gray-700 dark:text-primary-400 dark:hover:text-primary-300"
        >
            {{ $t('back_to_login') }}
        </Link>
    </div>
</template>
<script setup>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

defineOptions({ layout: AuthLayout })
</script>
