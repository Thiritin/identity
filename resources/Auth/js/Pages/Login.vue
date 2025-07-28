<template>
    <Head title="Sign In"></Head>
    
    <!-- Header section -->
    <div class="text-center mb-8">
        <Logo class="mx-auto w-16 h-16 mb-4" />
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Welcome back</h1>
        <p class="text-gray-600">Sign in to {{ clientName || 'Eurofurence' }}</p>
    </div>

    <!-- Error display -->
    <Message v-if="$page.props.flash?.error" severity="error" class="w-full mb-6 animate-slide-in">
        {{ $page.props.flash.error }}
    </Message>

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
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import Message from 'primevue/message'
import Logo from '@Auth/Pages/Logo.vue'
import IdentifyStep from '@Auth/Pages/Login/IdentifyStep.vue'
import AuthenticateStep from '@Auth/Pages/Login/AuthenticateStep.vue'
import AuthLayout from '@Shared/Layouts/AuthLayout.vue'

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
    window.location.href = route('auth.login.view', {
        login_challenge: props.login_challenge
    })
}
</script>

<style lang="scss">
// Override PrimeVue styles to match our design
:deep(.p-message) {
    border-radius: 0.75rem;
    margin: 0;
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
</style>