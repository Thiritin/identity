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
            <p v-if="form.invalid('email')" class="text-sm text-destructive text-center">{{ form.errors.email }}</p>
        </div>
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
import { Head, useForm } from '@inertiajs/vue3'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import { ArrowRight } from 'lucide-vue-next'

defineOptions({ layout: AuthLayout })

const form = useForm('post', route('auth.login.submit'), {
    email: null,
})

function submit() {
    form.submit()
}
</script>
