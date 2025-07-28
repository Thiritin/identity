<template>
    <Head title="Verfiy Email"></Head>
    <Logo></Logo>
    <LoginScreenWelcome
        :sub-title="$trans('verify_subtitle')"
        :title="$trans('verify_title')"
        class="mb-10"
    />
    <div class="space-y-8">
        <UserBox :user="user"/>
        <div
            class="text-sm shadow-md p-2 border-l-4 border-primary-600 dark:border-primary-300 dark:text-primary-300"
        >
            {{ $trans('verify_helptext') }}
        </div>

        <div
            v-if="props.status === 'verification-link-sent'"
            class="mb-4 font-medium text-sm text-green-600"
        >
            {{ $trans('verify_text_sent_to_your_mail') }}
        </div>

        <div
            v-if="props.status === 'throttled'"
            class="mb-4 font-medium text-sm text-red-600"
        >
            {{ $trans('too_many_attempts_email_verification') }}
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <Button
                    :loading="form.processing"
                    type="submit"
                    class="block"
                    :label="$trans('resend_verification_mail')"
                />

                <a
                    :href="route('auth.logout')"
                    as="button"
                    class="underline text-sm text-gray-600 hover:text-gray-900"
                >{{ $trans('logout') }}
                </a>
            </div>
        </form>
    </div>
</template>

<script setup>
import Logo from '@Auth/Pages/Logo.vue'
import LoginScreenWelcome from '@Auth/Pages/LoginScreenWelcome.vue'
import UserBox from './UserBox.vue'
import {Head, useForm} from '@inertiajs/vue3'
import {computed, ref} from 'vue'
import AuthLayout from "../../../Shared/js/Layouts/AuthLayout.vue";
import Button from "primevue/button";

defineOptions({
    layout: AuthLayout,
})

const props = defineProps({
    user: Object,
    status: String,
})

const form = useForm({
    email: props.user.email,
})

const buttonDisabled = ref(false)

const submit = () => {
    form.post(route('verification.send'), {
        preserveScroll: true,
        onSuccess: () => {
            buttonDisabled.value = true
            setTimeout(() => {
                buttonDisabled.value = false
            }, 30000)
        },
        onError: () => {
            form.reset()
        },
    })
}

const verificationLinkSent = computed(() => {
    return props.status === 'verification-link-sent'
})
</script>
