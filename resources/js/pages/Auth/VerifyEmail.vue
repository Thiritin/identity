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
        <Alert v-if="props.status === 'verification-link-sent'" variant="success" class="mb-6 animate-slide-in">
            <CheckCircleIcon class="h-4 w-4" />
            <AlertDescription>{{ $trans('verify_text_sent_to_your_mail') }}</AlertDescription>
        </Alert>

        <Alert v-if="props.status === 'throttled'" variant="destructive" class="mb-6 animate-slide-in">
            <ExclamationTriangleIcon class="h-4 w-4" />
            <AlertDescription>{{ $trans('too_many_attempts_email_verification') }}</AlertDescription>
        </Alert>

        <!-- Action buttons -->
        <div class="flex flex-col space-y-3">
            <Button
                @click="submit"
                :disabled="form.processing || buttonDisabled"
                class="w-full"
                size="lg"
            >
                <span v-if="form.processing">Processing...</span>
                <span v-else>{{ $trans('resend_verification_mail') }}</span>
            </Button>
            
            <Button
                as="a"
                :href="route('auth.logout')"
                variant="outline"
                class="w-full"
                size="lg"
            >
                {{ $trans('logout') }}
            </Button>
        </div>
    </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { TriangleAlert as ExclamationTriangleIcon, CheckCircle as CheckCircleIcon } from 'lucide-vue-next'
import Logo from '@/components/Logo.vue'
import UserBox from '../UserBox.vue'
import { Button } from '@/components/ui/button'
import { Alert, AlertDescription } from '@/components/ui/alert'
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
