<template>
    <Head title="Login"></Head>
    <div class="text-center">
        <Logo class="mx-auto"></Logo>
        <LoginScreenWelcome
            :sub-title="$trans('loginscreen_sign_in_to_continue')"
            :title="$trans('loginscreen_welcome')"
            class="mb-10"
        />
    </div>
    <form class="space-y-6" @submit.prevent="submit">
        <div>
            <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                {{ status }}
            </div>
            <div
                v-show="errors.nouser"
                class="w-full mb-4 bg-white dark:bg-primary-500 shadow-md py-4 px-3 border-l-[4px] border-red-600"
            >
                <span>{{ $trans('wrong_login_details_message') }}</span>
            </div>
            <div
                v-show="errors.general"
                class="w-full mb-4 bg-white dark:bg-primary-500 shadow-md py-4 px-3 border-l-[4px] border-red-600"
            >
                <span>{{ errors.general }}</span>
            </div>
        </div>
        <div class="space-y-4">
            <div class="flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <label for="email" class="text-sm text-gray-600 dark:text-primary-300">{{ $trans('email') }}</label>
                    <Link
                        :href="route('auth.login.view')"
                        class="text-xs text-gray-500 hover:text-gray-700 dark:text-primary-400 dark:hover:text-primary-300"
                    >{{ $trans('change') }}</Link>
                </div>
                <Input id="email"
                    :model-value="email"
                    disabled
                    class="bg-muted"
                />
            </div>
            <div class="flex flex-col gap-2">
                <label for="password" class="text-sm text-gray-600 dark:text-primary-300">{{ $trans('password') }}</label>
                <Input id="password"
                    type="password"
                    autocomplete="current-password"
                    @change="form.validate('password')"
                    :class="{ 'border-destructive': form.invalid('password') || errors.nouser }"
                    v-model.trim.lazy="form.password"
                />
                <p v-if="form.invalid('password')" class="text-sm text-destructive">{{ form.errors.password }}</p>
                <Link
                    :href="route('auth.forgot-password.view')"
                    class="text-xs text-gray-500 hover:text-gray-700 dark:text-primary-400 dark:hover:text-primary-300"
                >
                    {{ $trans('forgot_password_btn') }}
                </Link>
            </div>
            <div class="relative flex items-start">
                <div class="flex items-center h-5">
                    <Checkbox id="remember" :checked="form.remember" @update:checked="val => { console.log('checkbox changed:', val, typeof val); form.remember = val }" />
                </div>
                <div class="ml-3 text-sm">
                    <label
                        class="font-medium text-gray-700 dark:text-primary-300"
                        for="remember"
                    >{{ $trans('remember_me') }}</label>
                    <span class="ml-2 text-xs text-gray-400">[debug: {{ form.remember }}]</span>
                </div>
            </div>
        </div>
        <Button
            :disabled="form.processing"
            type="submit"
            class="w-full"
        >{{ $trans('sign_in') }} <ArrowRight class="size-4" /></Button>
    </form>
</template>
<script setup>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import { Checkbox } from '@/Components/ui/checkbox'
import { ArrowRight } from 'lucide-vue-next'

defineOptions({
    layout: AuthLayout,
})

const props = defineProps({
    status: String,
    errors: Object,
    email: String,
    loginChallenge: String,
})

const form = useForm('post', route('auth.login.password.submit'), {
    email: props.email,
    password: null,
    login_challenge: props.loginChallenge,
    remember: false,
})

function submit() {
    form.submit()
}
</script>
