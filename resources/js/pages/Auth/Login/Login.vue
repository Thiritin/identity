<template>
    <Head title="Sign In"></Head>
    
    <!-- Header section -->
    <div class="text-center mb-8">
        <Logo class="mx-auto w-16 h-16 mb-4" />
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Welcome back</h1>
        <p class="text-gray-600">Sign in to {{ clientName || 'Eurofurence' }}</p>
    </div>

    <!-- Error display -->
    <Alert v-if="$page.props.flash?.error" variant="destructive" class="mb-6 animate-slide-in">
        <ExclamationTriangleIcon class="h-4 w-4" />
        <AlertDescription>
            {{ $page.props.flash.error }}
        </AlertDescription>
    </Alert>

    <!-- Step 1: Identify user -->
    <IdentifyStep 
        v-if="step === 'identify'"
        @user-identified="handleUserIdentified"
        class="animate-slide-in"
    />

    <!-- Step 2: Authenticate -->
    <AuthenticateStep 
        v-else-if="step === 'authenticate'"
        :user="user"
        :auth-methods="authMethods"
        :webauthn-options="webauthnOptions"
        @authenticated="handleAuthenticated"
        @go-back="goBack"
        class="animate-slide-in"
    />
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { TriangleAlert as ExclamationTriangleIcon } from 'lucide-vue-next'
import { Alert, AlertDescription } from '@/components/ui/alert'
import Logo from '@/components/Logo.vue'
import IdentifyStep from './_partials/IdentifyStep.vue'
import AuthenticateStep from './_partials/AuthenticateStep.vue'
import AuthLayout from '@Shared/layouts/AuthLayout.vue'

defineOptions({
    layout: AuthLayout
})

const props = defineProps({
    step: {
        type: String,
        default: 'identify'
    },
    login_challenge: String,
    user: Object,
    authMethods: Object,
    webauthnOptions: Object,
    clientName: String
})

const handleUserIdentified = (userData) => {
    // This will be handled by the controller redirect
    console.log('User identified:', userData)
}

const handleAuthenticated = () => {
    // This will be handled by the controller redirect
    console.log('User authenticated')
}

const goBack = () => {
    // Navigate back to the login view to reset the flow
    // This clears the session and starts fresh with the identify step
    router.get(route('auth.login.view', {
        login_challenge: props.login_challenge
    }), {}, {
        replace: true // Replace the current history entry to prevent back button issues
    })
}
</script>

<style scoped>
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
</style>