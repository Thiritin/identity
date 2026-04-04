<template>
    <Head :title="$t('error_title_' + status, { fallback: 'Error ' + status })"></Head>
    <div class="text-center">
        <Logo class="mx-auto"></Logo>
        <LoginScreenWelcome
            :title="String(status)"
            :sub-title="$t('error_title_' + status)"
            class="mb-6"
        />
    </div>
    <p class="text-center text-sm text-gray-600 dark:text-primary-400">
        {{ $t('error_description_' + status) }}
    </p>
    <div class="mt-8 text-center">
        <a :href="homeUrl" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-300 dark:hover:text-primary-200">
            {{ $t('error_back_to_home') }}
        </a>
    </div>
</template>

<script>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head } from "@inertiajs/vue3"

export default {
    components: {
        Head,
        AuthLayout,
        Logo,
        LoginScreenWelcome,
    },
    layout: AuthLayout,

    props: {
        status: Number,
        homeUrl: String,
    },
}
</script>
