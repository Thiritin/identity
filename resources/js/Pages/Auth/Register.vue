<template>
    <auth-layout class='w-full'>
        <Head title='Home'/>
        <Logo></Logo>
        <LoginScreenWelcome :sub-title="$trans('register_subtitle')" :title="$trans('register_title')" class='mb-10'/>
        <form class='space-y-3' @submit.prevent='submit'>
            <FormInput id='username'
                       v-model.trim.lazy='form.username'
                       :placeholder="$trans('username')"
                       autocomplete='username'
                       :error='errors.username'
                       :label="$trans('username')"
                       type='text'/>
            <FormInput id='email' v-model.trim.lazy='form.email'
                       :error='errors.email'
                       :label="$trans('email')"
                       autocomplete='email' class='mb-4' placeholder='me@example.org' type='text'/>
            <FormInput id='password' v-model.trim.lazy='form.password'
                       :error='errors.password'
                       :label="$trans('password')"
                       autocomplete='password' class='mb-4' type='password'/>
            <PasswordInfoBox :correct-length='correctLength' :correct-lower-upper='correctLowerUpper'
                             :correct-number='correctNumber'
                             :correct-symbol='correctSymbol'/>
            <FormInput id='password_confirmation' v-model.trim.lazy='form.password_confirmation'
                       :label="$trans('password_confirmation')"
                       :error='errors?.password_confirmation'
                       autocomplete='password' class='mb-4' type='password'/>
            <div class='flex flex-col pt-10'>
                <button :class="form.processing ? 'bg-primary-400' : 'bg-primary-500'" :disabled='form.processing'
                        class='py-3 rounded-lg px-12 ml-auto text-white text-2xl mb-4 font-semibold focus:outline-none'
                        type='submit'>
                    {{ $trans('register_button') }}
                </button>
                <inertia-link :href="route('auth.login.view')" class='ml-auto text-gray-700 dark:text-gray-300'>
                    {{ $trans('register_back_to_login') }}
                </inertia-link>
            </div>
        </form>
    </auth-layout>
</template>

<script>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import FormInput from '@/Auth/Form/AuthFormInput.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import Head from '@inertiajs/vue3'
import PasswordInfoBox from '@/Auth/PasswordInfoBox.vue'

export default {
    components: {
        PasswordInfoBox,
        AuthLayout,
        Logo,
        LoginScreenWelcome,
        FormInput,
        Head,
    },
    /**
     * This is bad. I know it's replicated but it's 04:10am
     * If you know vue, feel free to write me on TG @Thiritin
     */
    computed: {
        correctLength() {
            if (this.form.password)
                return this.form.password.length >= 10
            return true
        },
        correctLowerUpper() {
            if (this.form.password)
                return this.form.password.match(/(\p{Ll}+.*\p{Lu})|(\p{Lu}+.*\p{Ll})/u)
            return true
        },
        correctNumber() {
            if (this.form.password)
                return this.form.password.match(/[0-9]/u)
            return true
        },
        correctSymbol() {
            if (this.form.password)
                return this.form.password.match(/\p{Z}|\p{S}|\p{P}/u)
            return true
        },
    },

    props: {
        errors: Object,
    },

    data() {
        return {
            form: this.$inertia.form({
                email: null,
                username: null,
                password: null,
                password_confirmation: null,
            }),
        }
    },

    methods: {
        submit() {
            this.form.post(this.route('auth.register.store'))
        },
    },
}
</script>
