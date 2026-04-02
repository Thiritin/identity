<template>
    <Head title="Reset Password"></Head>
    <div class="text-center">
        <Logo class="mx-auto"></Logo>
        <LoginScreenWelcome
            :sub-title="$trans('forgot_password_reset_sub_title')"
            :title="$trans('forgot_password_reset_title')"
            class="mb-10"
        />
    </div>
    <form v-if="!status" class="space-y-6" @submit.prevent="submit">
        <div class="space-y-4">
            <div
                class="text-sm shadow-md p-2 border-l-[4px] border-primary-600 dark:border-primary-300 dark:text-primary-300"
            >
                {{ $trans('forgot_password_helptext') }}
            </div>

            <div class="flex flex-col gap-2">
                <label for="email">{{ $trans('email') }}</label>
                <Input id="email"
                    placeholder="me@example.org"
                    :class="{ 'border-destructive': form.invalid('email') }"
                    @change="form.validate('email')"
                    v-model.trim.lazy="form.email"
                />
                <p v-if="form.invalid('email')" class="text-sm text-destructive">{{ form.errors.email }}</p>
            </div>
        </div>
        <div class="space-y-3">
            <Button
                :disabled="form.processing"
                type="submit"
                class="w-full"
            >{{ $trans('send_reset_mail') }}</Button>
            <Link
                :href="route('auth.login.view')"
                class="block text-center text-xs text-gray-500 hover:text-gray-700 dark:text-primary-400 dark:hover:text-primary-300"
            >
                {{ $trans('back_to_login') }}
            </Link>
        </div>
    </form>
    <div v-else>
        <div class="text-sm shadow-md p-2 border-l-[4px] border-primary-600">
            {{ status }}
        </div>
    </div>
</template>
<script setup>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'

defineOptions({ layout: AuthLayout })

const props = defineProps({
    status: String,
    errors: Object,
    canSeeLogin: Boolean,
})

const form = useForm('post', route('auth.forgot-password.store'), {
    email: null,
})

function submit() {
    form.submit()
}
</script>
