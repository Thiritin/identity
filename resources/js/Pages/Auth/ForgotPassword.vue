<template>
    <Head title="Reset Password"></Head>
    <div class="text-center">
        <Logo class="mx-auto"></Logo>
        <LoginScreenWelcome
            :sub-title="$t('forgot_password_reset_sub_title')"
            :title="$t('forgot_password_reset_title')"
            class="mb-10"
        />
    </div>
    <form v-if="!status" class="space-y-6" @submit.prevent="submit">
        <div class="space-y-4">
            <p class="text-sm text-muted-foreground">
                {{ $t('forgot_password_helptext') }}
            </p>

            <FormField
                id="email"
                :label="$t('email')"
                type="email"
                placeholder="me@example.org"
                v-model.trim.lazy="form.email"
                :error="form.errors.email"
                @change="form.validate('email')"
            />
        </div>
        <div class="space-y-3">
            <Button
                :disabled="form.processing"
                type="submit"
                class="w-full"
            >{{ $t('send_reset_mail') }}</Button>
            <Link
                :href="route('auth.login.view')"
                class="block text-center text-xs text-gray-500 hover:text-gray-700 dark:text-primary-400 dark:hover:text-primary-300"
            >
                {{ $t('back_to_login') }}
            </Link>
        </div>
    </form>
    <div v-else>
        <p class="text-sm text-muted-foreground">
            {{ status }}
        </p>
    </div>
</template>
<script setup>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import FormField from '@/Components/Auth/FormField.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
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
