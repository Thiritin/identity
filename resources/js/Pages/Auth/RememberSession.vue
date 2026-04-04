<template>
    <Head :title="$t('remember_session_title')"></Head>
    <div>
        <LoginScreenWelcome
            :title="$t('remember_session_title')"
            :sub-title="$t('remember_session_subtitle')"
        />
        <div class="mt-8 space-y-3">
            <Button
                class="w-full"
                :disabled="form.processing"
                @click="submitChoice(true)"
            >{{ $t('remember_session_yes') }}</Button>
            <Button
                variant="outline"
                class="w-full"
                :disabled="form.processing"
                @click="submitChoice(false)"
            >{{ $t('remember_session_no') }}</Button>
        </div>
    </div>
</template>
<script setup>
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'

defineOptions({
    layout: AuthLayout,
})

const form = useForm('post', route('auth.remember-session.submit'), {
    remember: false,
})

function submitChoice(remember) {
    form.remember = remember
    form.submit()
}
</script>
