<template>
    <Head title="Sign In"></Head>
    
    <!-- Auth0-inspired centered design -->
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden hover-lift">
                <!-- Header section -->
                <div class="px-8 pt-8 pb-6 text-center">
                    <Logo class="mx-auto w-16 h-16 mb-4" />
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Welcome back</h1>
                    <p class="text-gray-600">Sign in to {{ clientName || 'Eurofurence' }}</p>
                </div>

                <!-- Form section -->
                <div class="px-8 pb-8">
                    <!-- Error display -->
                    <div v-if="$page.props.flash?.error" class="mb-6 animate-slide-in">
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <ExclamationTriangleIcon class="h-5 w-5 text-red-500" />
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">{{ $page.props.flash.error }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

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
                </div>

                <!-- Footer -->
                <div class="px-8 py-6 bg-gray-50 border-t border-gray-100">
                    <div class="text-center text-sm text-gray-600">
                        <a href="/auth/register" class="font-medium text-blue-600 hover:text-blue-500 transition-colors">
                            Create account
                        </a>
                        <span class="mx-3 text-gray-400">"</span>
                        <a href="/help" class="font-medium text-blue-600 hover:text-blue-500 transition-colors">
                            Need help?
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import Logo from '@Auth/Pages/Logo.vue'
import IdentifyStep from '@Auth/Pages/ModernLogin/IdentifyStep.vue'
import AuthenticateStep from '@Auth/Pages/ModernLogin/AuthenticateStep.vue'
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
    window.location.href = route('auth.modern-login.view', {
        login_challenge: props.login_challenge
    })
}
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
</style>