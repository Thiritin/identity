<template>
    <Head title="Verify Email"></Head>
    
    <!-- Header section -->
    <div class="px-8 pt-8 pb-6 text-center">
        <Logo class="mx-auto w-16 h-16 mb-4" />
        <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $trans('verify_title') }}</h1>
        <p class="text-gray-600">{{ $trans('verify_subtitle') }}</p>
    </div>

    <!-- Content section -->
    <div class="px-8 pb-8">
        <!-- User info -->
        <div class="mb-6 animate-slide-in">
            <UserBox :user="user" class="bg-gray-50 rounded-lg p-4"/>
        </div>

        <!-- Help text -->
        <div class="mb-6 animate-slide-in">
            <div class="bg-blue-50 border-l-4 border-ef-green-primary rounded-lg p-4">
                <p class="text-sm text-gray-700">
                    {{ $trans('verify_helptext') }}
                </p>
            </div>
        </div>

        <!-- Status messages -->
        <div v-if="props.status === 'verification-link-sent'" class="mb-6 animate-slide-in">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <CheckCircleIcon class="h-5 w-5 text-green-500" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">{{ $trans('verify_text_sent_to_your_mail') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="props.status === 'throttled'" class="mb-6 animate-slide-in">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <ExclamationTriangleIcon class="h-5 w-5 text-red-500" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ $trans('too_many_attempts_email_verification') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action buttons -->
        <div class="flex flex-col space-y-3">
            <button
                @click="submit"
                :disabled="form.processing || buttonDisabled"
                class="w-full bg-ef-green-primary hover:bg-ef-green-dark disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-medium py-3 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-ef-green-primary focus:ring-offset-2"
            >
                <span v-if="form.processing">Processing...</span>
                <span v-else>{{ $trans('resend_verification_mail') }}</span>
            </button>
            
            <a
                :href="route('auth.logout')"
                class="w-full bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 font-medium py-3 px-4 rounded-lg transition-colors text-center"
            >
                {{ $trans('logout') }}
            </a>
        </div>
    </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { ExclamationTriangleIcon, CheckCircleIcon } from '@heroicons/vue/24/outline'
import Logo from './Logo.vue'
import UserBox from './UserBox.vue'
import AuthLayout from "@Shared/layouts/AuthLayout.vue"

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

<style scoped>
/* Auth0-inspired modern styling */
.auth-container {
    background: linear-gradient(135deg, #f8fafc 0%, #e0f2fe 100%);
}

/* Smooth transitions for all interactive elements */
* {
    transition: all 0.2s ease-in-out;
}

/* Custom shadow for the login card */
.shadow-auth {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Focus states with modern blue accent */
.focus-accent:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Hover states */
.hover-lift:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
}

/* Progressive disclosure animation */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-slide-in {
    animation: slideIn 0.3s ease-out;
}

.bg-ef-green-primary {
    background-color: #1a5f3f;
}

.bg-ef-green-dark {
    background-color: #0f3d27;
}

.focus\:ring-ef-green-primary:focus {
    --tw-ring-color: rgba(26, 95, 63, 0.5);
}

.focus\:border-ef-green-primary:focus {
    border-color: #1a5f3f;
}
</style>
