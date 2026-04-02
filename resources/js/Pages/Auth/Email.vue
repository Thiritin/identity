<template>
    <Head title="Login" />
    <Logo />
    <LoginScreenWelcome
        :title="$trans('loginscreen_welcome')"
        :sub-title="$trans('loginscreen_sign_in_to_continue')"
        class="mb-10"
    />
    <form @submit.prevent="submit">
        <div class="flex flex-col gap-2">
            <label for="email">{{ $trans('email') }}</label>
            <div class="relative">
                <Input
                    id="email"
                    type="email"
                    autocomplete="email"
                    @change="form.validate('email')"
                    :class="{ 'border-destructive': form.invalid('email') }"
                    class="pr-10"
                    v-model.trim.lazy="form.email"
                />
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-md p-1.5 text-primary-500 hover:text-primary-700 hover:bg-primary-100 dark:text-primary-300 dark:hover:text-primary-100 dark:hover:bg-primary-800 disabled:opacity-50 transition-colors"
                >
                    <ArrowRight class="size-4" />
                </button>
            </div>
            <p v-if="form.invalid('email')" class="text-sm text-destructive">{{ form.errors.email }}</p>
        </div>
    </form>
</template>
<script setup>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Input } from '@/Components/ui/input'
import { ArrowRight } from 'lucide-vue-next'

defineOptions({ layout: AuthLayout })

const form = useForm('post', route('auth.login.submit'), {
    email: null,
})

function submit() {
    form.submit()
}
</script>
