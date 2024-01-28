<template>
    <Head title="Login"></Head>
    <Logo></Logo>
    <LoginScreenWelcome
        :sub-title="$trans('loginscreen_sign_in_to_continue')"
        :title="$trans('loginscreen_welcome')"
        class="mb-10"
    />
    <form class="space-y-12" @submit.prevent="submit">
        <!-- Login Form -->
        <div>
            <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                {{ status }}
            </div>
            <div
                v-show="errors.nouser"
                class="w-full mb-8 bg-white dark:bg-primary-500 shadow-md py-4 px-3 border-l-[4px] border-red-600"
            >
                <span>{{ $trans('wrong_login_details_message') }}</span>
            </div>

            <div
                v-show="errors.general"
                class="w-full mb-8 bg-white dark:bg-primary-500 shadow-md py-4 px-3 border-l-[4px] border-red-600"
            >
                <span>{{ errors.general }}</span>
            </div>
        </div>
        <div class="space-y-4">
            <FormInput
                id="email"
                v-model.trim.lazy="form.email"
                :error="errors?.email"
                :placeholder="$trans('email')"
                :label="$trans('email')"
                autocomplete="email"
                autofocus
                class="mb-4"
                type="email"
            />
            <FormInput
                id="password"
                v-model.lazy="form.password"
                :error="errors?.password"
                :label="$trans('password')"
                :placeholder="$trans('password')"
                autocomplete="password"
                class="mb-16"
                type="password"
            />
            <div class="relative flex items-start">
                <div class="flex items-center h-5">
                    <input
                        id="remember"
                        v-model.lazy="form.remember"
                        class="form-checkbox focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded"
                        name="remember"
                        type="checkbox"
                    />
                </div>
                <div class="ml-3 text-sm">
                    <label
                        class="font-medium text-gray-700 dark:text-primary-300"
                        for="remember"
                    >{{ $trans('remember_me') }}</label
                    >
                </div>
            </div>
        </div>
        <div class="flex flex-col">
            <button
                :class="form.processing ? 'bg-primary-400' : 'bg-primary-500'"
                :disabled="form.processing"
                class="py-3 rounded-lg px-12 ml-auto text-white text-2xl mb-4 font-semibold focus:outline-none"
                type="submit"
            >
                {{ $trans('sign_in') }}
            </button>
            <Link
                :href="route('auth.forgot-password.view')"
                class="ml-auto text-gray-700 dark:text-primary-300"
            >
                {{ $trans('forgot_password_btn') }}
            </Link>
        </div>
    </form>
</template>
<script>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import FormInput from '@/Auth/Form/AuthFormInput.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import {Head, Link} from '@inertiajs/vue3'

export default {
    components: {Head, AuthLayout, Logo, LoginScreenWelcome, FormInput, Link},
    layout: AuthLayout,
    props: {status: String, errors: Object},
    data() {
        return {
            form: this.$inertia.form({
                email: null,
                password: null,
                login_challenge: null,
                remember: false,
            }),
            show: true,
        }
    },
    methods: {
        submit() {
            const urlParams = new URLSearchParams(window.location.search)
            this.form
                .transform((data) => ({
                    ...data,
                    login_challenge: urlParams.get('login_challenge'),
                }))
                .post(this.route('auth.login.submit'))
        },
    },
}
</script>
