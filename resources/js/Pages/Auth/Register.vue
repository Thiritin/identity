<template>
    <Head title="Register"/>
    <div class="text-center">
        <Logo class="mx-auto"></Logo>
        <LoginScreenWelcome
            :sub-title="$t('register_subtitle')"
            :title="$t('register_title')"
            class="mb-10"
        />
    </div>
    <form class="space-y-4" @submit.prevent="submit">
        <Transition name="field-error">
            <p v-if="$page.props.errors.throttle" class="text-xs text-destructive text-center">{{ $page.props.errors.throttle }}</p>
        </Transition>
        <FormField
            id="username"
            :label="$t('username')"
            v-model.trim.lazy="form.username"
            :error="form.errors.username"
            @change="form.validate('username')"
        />
        <FormField
            id="email"
            :label="$t('email')"
            type="email"
            autocomplete="email"
            v-model="form.email"
            :error="form.errors.email"
        />
        <FormField
            id="password"
            :label="$t('password')"
            type="password"
            autocomplete="new-password"
            v-model.trim.lazy="form.password"
            :error="form.errors.password"
            @change="form.validate('password')"
        />
        <PasswordInfoBox
            :password="form.password"
        />
        <HoneypotFields :honeypot="form" />
        <div class="pt-4 space-y-3">
            <Button
                :disabled="form.processing"
                type="submit"
                class="w-full"
            >{{ $t('register_button') }} <ArrowRight class="size-4" /></Button>
            <Link
                :href="route('auth.login.view')"
                class="block text-center text-xs text-gray-500 hover:text-gray-700 dark:text-primary-400 dark:hover:text-primary-300"
            >
                {{ $t('register_back_to_login') }}
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
import { Head, Link, useForm } from '@inertiajs/vue3'
import PasswordInfoBox from '@/Auth/PasswordInfoBox.vue'
import { Button } from '@/Components/ui/button'
import { ArrowRight } from 'lucide-vue-next'
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
    ...useHoneypot(),
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
