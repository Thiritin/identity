<template>
    <Head title="Login" />
    <Logo />
    <LoginScreenWelcome
        :title="$trans('loginscreen_welcome')"
        :sub-title="$trans('loginscreen_sign_in_to_continue')"
        class="mb-10"
    />
    <form class="space-y-12" @submit.prevent="submit">
        <div class="space-y-4">
            <div class="flex flex-col gap-2">
                <label for="email">{{ $trans('email') }}</label>
                <Input
                    id="email"
                    type="email"
                    autocomplete="email"
                    @change="form.validate('email')"
                    :class="{ 'border-destructive': form.invalid('email') }"
                    v-model.trim.lazy="form.email"
                />
                <p v-if="form.invalid('email')" class="text-sm text-destructive">{{ form.errors.email }}</p>
            </div>
        </div>
        <div class="flex flex-row justify-end">
            <Button :disabled="form.processing" type="submit">
                {{ $trans('continue') }}
            </Button>
        </div>
    </form>
</template>
<script setup>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'

defineOptions({ layout: AuthLayout })

const form = useForm('post', route('auth.login.submit'), {
    email: null,
})

function submit() {
    form.submit()
}
</script>
