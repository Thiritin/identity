<template>
    <Head title="Error"></Head>
    
    <!-- Header section -->
    <div class="px-8 pt-8 pb-6 text-center">
        <Logo class="mx-auto w-16 h-16 mb-4" />
        <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ title }}</h1>
    </div>

    <!-- Error section -->
    <div class="px-8 pb-8">
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 animate-slide-in">
            <div class="flex">
                <div class="flex-shrink-0">
                    <ExclamationTriangleIcon class="h-5 w-5 text-red-500" />
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">{{ description }}</p>
                </div>
            </div>
        </div>

        <!-- Back to login button -->
        <div class="mt-6">
            <Link
                :href="route('auth.login.view')"
                class="w-full bg-ef-green-primary hover:bg-ef-green-dark text-white font-medium py-3 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-ef-green-primary focus:ring-offset-2 block text-center"
            >
                Back to Login
            </Link>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import Logo from '@Auth/Pages/Logo.vue'
import AuthLayout from '@Shared/Layouts/AuthLayout.vue'

defineOptions({
    layout: AuthLayout
})

defineProps({
    title: String,
    description: String,
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
