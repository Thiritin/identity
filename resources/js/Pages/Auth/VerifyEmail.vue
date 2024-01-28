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
            class="text-sm shadow-md p-2 border-l-[4px] border-primary-600 dark:border-primary-300 dark:text-primary-300"
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
                <button
                    :class="
                        form.processing ? 'bg-primary-400' : 'bg-primary-500'
                    "
                    :disabled="form.processing || buttonDisabled"
                    class="py-3 rounded-lg px-8 mr-auto text-white text-sm mb-4 font-semibold focus:outline-none"
                >
                    {{ $trans('resend_verification_mail') }}
                </button>

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
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import UserBox from '@/Pages/Auth/UserBox.vue'
import {Head, useForm} from '@inertiajs/vue3'
import {computed, ref} from 'vue'
import AuthLayout from "../../Layouts/AuthLayout.vue";

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
