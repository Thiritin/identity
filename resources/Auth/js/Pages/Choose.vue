<template>
    <Head title="Choose"></Head>
    <Logo></Logo>
    <LoginScreenWelcome
        :sub-title="$trans('choose_sub_title')"
        :title="$trans('choose_title')"
        class="mb-10"
    />
    <a
        :href="route('login.apps.redirect', 'portal')"
        class="block bg-primary-600 text-gray-100 px-4 py-4 rounded font-medium text-center transition duration-75 hover:bg-primary-500 cursor-pointer mb-4"
    >
        {{ $trans('choose_login_with_existing_account') }}
    </a>
    <Link
        :href="route('auth.register.view')"
        class="block dark:bg-primary-800 bg-gray-200 text-gray-800 dark:text-gray-200 dark:hover:bg-primary-700 px-4 py-4 rounded font-medium text-center transition duration-75 hover:bg-gray-300 cursor-pointer"
    >
        {{ $trans('choose_create_new_account') }}
    </Link>
</template>

<script>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import {Head, Link} from '@inertiajs/vue3'

export default {
    components: {
        Head,
        AuthLayout,
        Logo,
        LoginScreenWelcome,
        Link,
    },
    layout: AuthLayout,
}
</script>
