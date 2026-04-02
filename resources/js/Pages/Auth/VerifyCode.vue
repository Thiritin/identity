<template>
    <Head title="Verify Email" />
    <div class="text-center">
        <Logo class="mx-auto" />
        <LoginScreenWelcome
            :title="$t('verify_code_title')"
            :sub-title="$t('verify_code_subtitle')"
            class="mb-10"
        />
    </div>
    <form class="space-y-4" @submit.prevent="submit">
        <Transition name="field-error">
            <p v-if="$page.props.flash?.status === 'code-resent'" class="text-xs text-green-600 text-center">{{ $t('verify_code_resent') }}</p>
        </Transition>
        <Transition name="field-error">
            <p v-if="$page.props.errors.throttle" class="text-xs text-destructive text-center">{{ $page.props.errors.throttle }}</p>
        </Transition>
        <div class="flex flex-col items-center gap-2">
            <label for="code" class="text-sm text-gray-600 dark:text-primary-300">{{ $t('verify_code_label') }}</label>
            <Input
                id="code"
                v-model="form.code"
                type="text"
                autocomplete="one-time-code"
                maxlength="6"
                placeholder="XXXXXX"
                class="text-center text-2xl tracking-[0.5em] indent-[0.5em] font-mono w-52 uppercase"
            />
            <Transition name="field-error">
                <p v-if="form.errors.code" class="text-xs text-destructive">{{ form.errors.code }}</p>
            </Transition>
        </div>
        <Button
            :disabled="form.processing || !form.code || form.code.length < 6"
            type="submit"
            class="w-full"
        >{{ $t('verify_code_submit') }} <ArrowRight class="size-4" /></Button>
        <button
            type="button"
            :disabled="resendForm.processing"
            class="block w-full text-center text-xs text-gray-500 hover:text-gray-700 dark:text-primary-400 dark:hover:text-primary-300 disabled:opacity-50"
            @click="resend"
        >
            {{ $t('verify_code_resend') }}
        </button>
    </form>
</template>
<script setup>
import { watch } from 'vue'
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import { ArrowRight } from 'lucide-vue-next'

defineOptions({ layout: AuthLayout })

const form = useForm('post', route('auth.register.code.submit'), {
    code: null,
})

watch(() => form.code, (value) => {
    if (value && value.length === 6 && /^[A-Za-z0-9]{6}$/.test(value) && !form.processing) {
        submit()
    }
})

const resendForm = useForm('post', route('auth.register.code.resend'), {})

function submit() {
    form.submit()
}

function resend() {
    resendForm.submit()
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
