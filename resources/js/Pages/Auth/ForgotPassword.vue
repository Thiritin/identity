<template>
    <Head title="Reset Password"></Head>
    <Logo></Logo>
    <LoginScreenWelcome
        :sub-title="$trans('forgot_password_reset_sub_title')"
        :title="$trans('forgot_password_reset_title')"
        class="mb-10"
    />
    <form v-if="!status" class="space-y-12" @submit.prevent="submit">
        <div class="space-y-6">
            <div
                class="text-sm shadow-md p-2 border-l-[4px] border-primary-600 dark:border-primary-300 dark:text-primary-300"
            >
                {{ $trans('forgot_password_helptext') }}
            </div>
            <FormInput
                id="email"
                v-model.trim.lazy="form.email"
                :class="{
                    'border-red-500 focus:border-red-500':
                        errors?.email != null,
                }"
                :error="errors.email"
                :label="$trans('email')"
                autocomplete="email"
                placeholder="me@example.com"
                type="email"
            />
        </div>
        <div class="flex flex-col">
            <button
                :class="
                    form.processing || status !== undefined
                        ? 'bg-primary-400'
                        : 'bg-primary-500'
                "
                :disabled="form.processing || status !== undefined"
                class="py-3 rounded-lg px-12 ml-auto text-white text-2xl mb-4 font-semibold focus:outline-none"
                type="submit"
            >
                {{ $trans('send_reset_mail') }}
            </button>
            <Link
                :href="route('auth.login.view')"
                class="ml-auto text-gray-700 dark:text-primary-300"
            >
                {{ $trans('back_to_login') }}
            </Link>
        </div>
    </form>
    <div v-else>
        <div class="text-sm shadow-md p-2 border-l-[4px] border-primary-600">
            {{ status }}
        </div>
    </div>
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
    props: {status: String, errors: Object, canSeeLogin: Boolean},
    data() {
        return {form: this.$inertia.form({email: null}), show: true}
    },
    methods: {
        submit() {
            this.form.post(this.route('auth.forgot-password.store'))
        },
    },
}
</script>
