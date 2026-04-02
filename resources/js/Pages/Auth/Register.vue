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
            autocomplete="new-password"
            v-model.trim.lazy="form.password"
            :error="form.errors.password"
            @change="form.validate('password')"
        />
        <PasswordInfoBox
            :password="form.password"
        />
        <AltchaWidget v-model="form.altcha" />
        <Transition name="field-error">
            <p v-if="form.errors.altcha" class="text-xs text-destructive text-center">{{ form.errors.altcha }}</p>
        </Transition>
        <HoneypotFields :honeypot="form" />
        <div class="pt-4 space-y-3">
            <Button
                :disabled="form.processing || !form.altcha"
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
import HoneypotFields from '@/Components/Auth/HoneypotFields.vue'
import AltchaWidget from '@/Components/Auth/AltchaWidget.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import PasswordInfoBox from '@/Auth/PasswordInfoBox.vue'
import { Button } from '@/Components/ui/button'
import { useHoneypot } from '@/Composables/useHoneypot'

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
    altcha: null,
    ...useHoneypot(),
})

function submit() {
    form.submit()
}
</script>
