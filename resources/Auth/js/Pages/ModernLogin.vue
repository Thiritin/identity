<template>
    <Head title="Sign In"></Head>
    
    <div class="min-h-screen bg-background-modern flex">
        <!-- Left side - Branding (hidden on mobile) -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-ef-green-primary to-ef-green-light items-center justify-center p-12">
            <div class="max-w-md text-center text-white">
                <Logo class="mx-auto mb-8 w-24 h-24" />
                <h1 class="text-4xl font-bold mb-4">Welcome to {{ $trans('app_name') }}</h1>
                <p class="text-xl opacity-90">Secure authentication for the Eurofurence community</p>
            </div>
        </div>

        <!-- Right side - Login form -->
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <!-- Mobile logo -->
                <div class="lg:hidden text-center mb-8">
                    <Logo class="mx-auto w-16 h-16 mb-4" />
                    <h1 class="text-2xl font-bold text-text-primary">Sign in</h1>
                </div>

                <!-- Desktop header -->
                <div class="hidden lg:block mb-8">
                    <h1 class="text-3xl font-bold text-text-primary mb-2">Sign in</h1>
                    <p class="text-text-secondary">to continue to {{ clientName || 'Eurofurence' }}</p>
                </div>

                <!-- Error display -->
                <div v-if="$page.props.flash?.error" class="mb-6">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-800">{{ $page.props.flash.error }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 1: Identify user -->
                <IdentifyStep 
                    v-if="step === 'identify'"
                    @user-identified="handleUserIdentified"
                />

                <!-- Step 2: Authenticate -->
                <AuthenticateStep 
                    v-else-if="step === 'authenticate'"
                    :user="user"
                    :auth-methods="authMethods"
                    :webauthn-options="webauthnOptions"
                    @authenticated="handleAuthenticated"
                    @go-back="goBack"
                />

                <!-- Footer -->
                <div class="mt-8 text-center">
                    <div class="text-sm text-text-secondary">
                        <a href="/auth/register" class="font-medium text-ef-green-primary hover:text-ef-green-dark">
                            Create account
                        </a>
                        <span class="mx-2">•</span>
                        <a href="/help" class="font-medium text-ef-green-primary hover:text-ef-green-dark">
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
.bg-background-modern {
    background-color: #fafbfc;
}

.text-text-primary {
    color: #202124;
}

.text-text-secondary {
    color: #5f6368;
}

.text-ef-green-primary {
    color: #1a5f3f;
}

.text-ef-green-dark {
    color: #0f3d27;
}

.bg-ef-green-primary {
    background-color: #1a5f3f;
}

.bg-ef-green-light {
    background-color: #2d7a5f;
}

.from-ef-green-primary {
    --tw-gradient-from: #1a5f3f;
}

.to-ef-green-light {
    --tw-gradient-to: #2d7a5f;
}

.border-ef-green-primary {
    border-color: #1a5f3f;
}

.hover\:bg-ef-green-dark:hover {
    background-color: #0f3d27;
}

.focus\:border-ef-green-primary:focus {
    border-color: #1a5f3f;
}

.focus\:ring-ef-green-primary:focus {
    --tw-ring-color: #1a5f3f;
}
</style>