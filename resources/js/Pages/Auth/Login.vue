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
        <Transition name="field-error">
            <p v-if="$page.props.errors.throttle" class="text-xs text-destructive">{{ $page.props.errors.throttle }}</p>
        </Transition>
        <div class="space-y-4">
            <FormField
                id="email"
                :label="$trans('email')"
                type="email"
                autocomplete="email"
                v-model="form.email"
                :error="form.errors.email"
            />
            <div class="flex flex-col gap-2">
                <label for="password" class="text-sm text-gray-600 dark:text-primary-300">{{ $trans('password') }}</label>
                <Input id="password"
                    type="password"
                    autocomplete="current-password"
                    @change="form.validate('password')"
                    :class="{ 'border-destructive': form.invalid('password') || errors.nouser }"
                    v-model.trim.lazy="form.password"
                />
                <Transition name="field-error">
                    <p v-if="form.invalid('password')" class="text-xs text-destructive">{{ form.errors.password }}</p>
                </Transition>
                <Transition name="field-error">
                    <p v-if="errors.nouser" class="text-xs text-destructive">{{ $trans('wrong_login_details_message') }}</p>
                </Transition>
                <Transition name="field-error">
                    <p v-if="errors.general" class="text-xs text-destructive">{{ errors.general }}</p>
                </Transition>
                <Link
                    :href="route('auth.forgot-password.view')"
                    class="text-xs text-gray-500 hover:text-gray-700 dark:text-primary-400 dark:hover:text-primary-300"
                >
                    {{ $trans('forgot_password_btn') }}
                </Link>
            </div>
            <div class="relative flex items-start">
                <div class="flex items-center h-5">
                    <Checkbox id="remember" v-model="form.remember" />
                </div>
                <div class="ml-3 text-sm">
                    <label
                        class="font-medium text-gray-700 dark:text-primary-300"
                        for="remember"
                    >{{ $trans('remember_me') }}</label>
                </div>
            </div>
        </div>
        <Button
            :disabled="form.processing"
            type="submit"
            class="w-full"
        >{{ $trans('sign_in') }} <ArrowRight class="size-4" /></Button>
        <Link
            :href="route('auth.register.view')"
            class="block text-center text-xs text-gray-500 hover:text-gray-700 dark:text-primary-400 dark:hover:text-primary-300"
        >
            {{ $trans('choose_create_new_account') }}
        </Link>
    </form>
</template>
<script setup>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import FormField from '@/Components/Auth/FormField.vue'
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
<style scoped>
.field-error-enter-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.field-error-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.field-error-enter-from,
.field-error-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
