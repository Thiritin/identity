<template>

    <auth-layout>
        <Logo></Logo>
        <LoginScreenWelcome :sub-title="$trans('loginscreen_sign_in_to_continue')"
                            :title="$trans('loginscreen_welcome')"
                            class="mb-10"/>
        <form class="space-y-12" @submit.prevent="submit">
            <div>
                <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                    {{ status }}
                </div>
                <div v-if="!canSeeLogin" class="w-full mb-8 bg-white shadow-md py-4 px-3 border-l-[4px] border-red-600">
                    <span>{{ $trans('no_login_challenge') }}</span>
                </div>
                <div v-show="errors.nouser"
                     class="w-full mb-8 bg-white shadow-md py-4 px-3 border-l-[4px] border-red-600">
                    <span>{{ $trans('wrong_login_details_message') }}</span>
                </div>
            </div>
            <div v-if="canSeeLogin" class="space-y-4">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700" for="email"> {{ $trans('email') }} </label>
                    <FormInput id="email"
                               v-model.trim.lazy="form.email"
                               :class="{'border-red-500 focus:border-red-500': (errors?.email != null)}"
                               :placeholder="$trans('email')"
                               autocomplete="email"
                               class="mb-4"
                               type="email"/>
                </div>
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700" for="password"> {{ $trans('password')
                        }} </label>
                    <FormInput id="password"
                               v-model.lazy="form.password"
                               :class="{'border-red-500 focus:border-red-500': (errors?.password != null)}"
                               :placeholder="$trans('password')"
                               autocomplete="password"
                               class="mb-16"
                               type="password"/>
                </div>
            </div>
            <div v-if="canSeeLogin" class="flex flex-col">
                <button :class="form.processing ? 'bg-primary-400' : 'bg-primary-500'"
                        :disabled="form.processing"
                        class="py-3 rounded-lg px-12 ml-auto text-white text-2xl mb-4 font-semibold focus:outline-none"
                        type="submit">
                    {{ $trans('sign_in') }}
                </button>
                <inertia-link :href="route('auth.forgot-password.view')" class="ml-auto text-gray-700">
                    {{ $trans('forgot_password_btn') }}
                </inertia-link>
            </div>
        </form>
    </auth-layout>
</template>

<script>
import Logo from "@/Auth/Logo";
import LoginScreenWelcome from "@/Auth/LoginScreenWelcome";
import FormInput from "@/Auth/Form/FormInput";
import ValidationErrors from "@/Jetstream/ValidationErrors";
import AuthLayout from "@/Layouts/AuthLayout";

export default {
    components: {
        AuthLayout,
        ValidationErrors,
        Logo,
        LoginScreenWelcome,
        FormInput
    },

    props: {
        status: String,
        errors: Object,
        canSeeLogin: Boolean
    },

    data() {
        return {
            form: this.$inertia.form({
                email: null,
                password: null,
                login_challenge: null,
                remember: false
            }),
            show: true
        }
    },

    methods: {
        submit() {
            const urlParams = new URLSearchParams(window.location.search);

            this.form
                .transform(data => ({
                    ...data,
                    login_challenge: urlParams.get('login_challenge')
                }))
                .post(this.route('auth.login.submit'))
        }
    }
}
</script>

