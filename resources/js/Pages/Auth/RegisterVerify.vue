<template>
    <Head title="Verify" />
    <div class="text-center">
        <Logo class="mx-auto" />
        <LoginScreenWelcome
            :title="$t('register_verify_title')"
            :sub-title="$t('register_verify_subtitle')"
            class="mb-10"
        />
    </div>
    <form class="space-y-4" @submit.prevent="submit">
        <AltchaWidget v-model="form.altcha" />
        <Transition name="field-error">
            <p v-if="form.errors.altcha" class="text-xs text-destructive text-center">{{ form.errors.altcha }}</p>
        </Transition>
        <Transition name="field-error">
            <p v-if="$page.props.errors.throttle" class="text-xs text-destructive text-center">{{ $page.props.errors.throttle }}</p>
        </Transition>
        <Button
            :disabled="form.processing || !form.altcha"
            type="submit"
            class="w-full"
        >{{ $t('continue') }} <ArrowRight class="size-4" /></Button>
    </form>
</template>
<script setup>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import AltchaWidget from '@/Components/Auth/AltchaWidget.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { ArrowRight } from 'lucide-vue-next'

defineOptions({ layout: AuthLayout })

const form = useForm('post', route('auth.register.verify.submit'), {
    altcha: null,
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
