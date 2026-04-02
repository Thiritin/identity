<template>
    <Head title="Register"/>
    <div class="text-center">
        <Logo class="mx-auto"></Logo>
        <LoginScreenWelcome
            :sub-title="$trans('register_subtitle')"
            :title="$trans('register_title')"
            class="mb-10"
        />
    </div>
    <form class="space-y-4" @submit.prevent="submit">
        <FormField
            id="username"
            :label="$trans('username')"
            v-model.trim.lazy="form.username"
            :error="form.errors.username"
            @change="form.validate('username')"
        />
        <FormField
            id="email"
            :label="$trans('email')"
            type="email"
            autocomplete="email"
            v-model="form.email"
            :error="form.errors.email"
        />
        <FormField
            id="password"
            :label="$trans('password')"
            type="password"
            autocomplete="password"
            v-model.trim.lazy="form.password"
            :error="form.errors.password"
            @change="form.validate('password')"
        />
        <PasswordInfoBox
            :password="form.password"
        />
        <FormField
            id="password_confirmation"
            :label="$trans('password_confirmation')"
            type="password"
            autocomplete="password"
            v-model.trim.lazy="form.password_confirmation"
            :error="form.errors.password_confirmation"
            @change="form.validate('password_confirmation')"
        />
        <div class="pt-4 space-y-3">
            <Button
                :disabled="form.processing"
                type="submit"
                class="w-full"
            >{{ $trans('register_button') }}</Button>
            <Link
                :href="route('auth.login.view')"
                class="block text-center text-xs text-gray-500 hover:text-gray-700 dark:text-primary-400 dark:hover:text-primary-300"
            >
                {{ $trans('register_back_to_login') }}
            </Link>
        </div>
    </form>
</template>
<script setup>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import FormField from '@/Components/Auth/FormField.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import PasswordInfoBox from '@/Auth/PasswordInfoBox.vue'
import { Button } from '@/Components/ui/button'

defineOptions({
    layout: AuthLayout,
})

const props = defineProps({
    email: String,
    errors: Object,
})

const form = useForm('post', route('auth.register.store'), {
    email: props.email,
    username: null,
    password: null,
    password_confirmation: null,
})

function submit() {
    form.submit()
}
</script>
