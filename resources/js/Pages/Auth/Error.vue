<template>
    <Head :title="pageTitle"></Head>
    <div class="text-center">
        <Logo class="mx-auto"></Logo>
        <LoginScreenWelcome
            :title="headingTitle"
            :sub-title="pageTitle"
            class="mb-6"
        />
    </div>
    <p class="text-center text-sm text-gray-600 dark:text-primary-400">
        {{ pageDescription }}
    </p>
    <div class="mt-8 text-center">
        <a :href="homeUrl ?? '/'" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-300 dark:hover:text-primary-200">
            {{ $t('error_back_to_home') }}
        </a>
    </div>
</template>

<script>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head } from "@inertiajs/vue3"
import { trans } from 'laravel-vue-i18n'

export default {
    components: {
        Head,
        Logo,
        LoginScreenWelcome,
    },
    layout: AuthLayout,

    props: {
        status: Number,
        homeUrl: String,
        error: String,
    },

    computed: {
        headingTitle() {
            if (this.status) {
                return String(this.status)
            }
            return '!'
        },
        pageTitle() {
            if (this.status) {
                return trans('error_title_' + this.status)
            }
            if (this.error) {
                return trans('auth_error_' + this.error)
            }
            return trans('auth_error_unknown')
        },
        pageDescription() {
            if (this.status) {
                return trans('error_description_' + this.status)
            }
            if (this.error) {
                return trans('auth_error_description_' + this.error)
            }
            return trans('auth_error_description_unknown')
        },
    },
}
</script>
