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
                class="w-full mb-8 bg-white dark:bg-primary-500 shadow-md py-4 px-3 border-l-4 border-red-600"
            >
                <span>{{ $trans('wrong_login_details_message') }}</span>
            </div>

            <div
                v-show="errors.general"
                class="w-full mb-8 bg-white dark:bg-primary-500 shadow-md py-4 px-3 border-l-4 border-red-600"
            >
                <span>{{ errors.general }}</span>
            </div>
        </div>
        <div class="space-y-4">
            <div class="flex flex-col gap-2">
                <label for="email">{{ $trans('email') }}</label>
                <InputText id="email"
                           autocomplete="email"
                           @change="form.validate('email')"
                           :invalid="form.invalid('email') || errors.nouser"
                           v-model.trim.lazy="form.email"
                />
                <InlineMessage v-if="form.invalid('email')" severity="error">{{ form.errors.email }}</InlineMessage>
            </div>
            <div class="flex flex-col gap-2">
                <label for="password">{{ $trans('password') }}</label>
                <InputText id="password"
                           type="password"
                           autocomplete="current-password"
                           @change="form.validate('password')"
                           :invalid="form.invalid('password') || errors.nouser"
                           v-model.trim.lazy="form.password"
                />
                <InlineMessage v-if="form.invalid('password')" severity="error">{{ form.errors.password }}
                </InlineMessage>
            </div>
            <div class="relative flex items-start">
                <div class="flex items-center h-5">
                    <Checkbox input-id="remember" :binary="true" v-model="form.remember" name="remember"/>
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
        <div class="flex flex-row justify-between">
            <Link
                :href="route('auth.forgot-password.view')"
                class="text-gray-700 dark:text-primary-300 block"
            >
                {{ $trans('forgot_password_btn') }}
            </Link>
            <Button
                :loading="form.processing"
                type="submit"
                :label="$trans('sign_in')"
            />
        </div>
    </form>
</template>
<script setup>
import Logo from '@Auth/Pages/Logo.vue'
import LoginScreenWelcome from '@Auth/Pages/LoginScreenWelcome.vue'
import FormInput from '@Auth/Pages/Form/AuthFormInput.vue'
import AuthLayout from '@Shared/Layouts/AuthLayout.vue'
import {Head, Link} from '@inertiajs/vue3'
import {ref} from 'vue'
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import InlineMessage from 'primevue/inlinemessage';
import {useForm} from 'laravel-precognition-vue-inertia';

defineOptions({
    layout: AuthLayout
})

const props = defineProps({
    status: String,
    errors: Object
})

const show = ref(true);
const form = useForm('post', route('auth.login.submit'), {
    email: null,
    password: null,
    login_challenge: null,
    remember: false,
})

function submit() {
    const urlParams = new URLSearchParams(window.location.search)
    form
        .transform((data) => ({
            ...data,
            login_challenge: urlParams.get('login_challenge'),
        }))
        .post(route('auth.login.submit'))
}
</script>
