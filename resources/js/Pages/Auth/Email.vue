<template>
    <Head title="Login" />
    <div class="text-center">
        <Logo class="mx-auto" />
        <LoginScreenWelcome
            :title="$trans('loginscreen_welcome')"
            :sub-title="$trans('enter_email_to_continue')"
            class="mb-10"
        />
    </div>
    <form class="space-y-4" @submit.prevent="submit">
        <Transition name="field-error">
            <p v-if="$page.props.errors.throttle" class="text-xs text-destructive text-center">{{ $page.props.errors.throttle }}</p>
        </Transition>
        <FormField
            id="email"
            :label="$trans('email')"
            type="email"
            autocomplete="email"
            placeholder="me@example.org"
            v-model.trim="form.email"
            :error="form.errors.email"
            @change="form.validate('email')"
        />
        <HoneypotFields :honeypot="form" />
        <Button :disabled="form.processing" type="submit" class="w-full">
            {{ $trans('continue') }}
            <ArrowRight class="size-4" />
        </Button>
    </form>
</template>
<script setup>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import FormField from '@/Components/Auth/FormField.vue'
import HoneypotFields from '@/Components/Auth/HoneypotFields.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { ArrowRight } from 'lucide-vue-next'
import { useHoneypot } from '@/Composables/useHoneypot'

defineOptions({ layout: AuthLayout })

const form = useForm('post', route('auth.login.submit'), {
    email: null,
    ...useHoneypot(),
})

function submit() {
    form.submit()
}
</script>
