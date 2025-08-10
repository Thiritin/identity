<template>
    <Head title="Choose Account"></Head>
    
    <!-- Header section -->
    <div class="pb-6 text-center">
        <Logo class="mx-auto w-16 h-16 mb-4" />
        <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $trans('choose_title') }}</h1>
        <p class="text-gray-600">{{ $trans('choose_sub_title') }}</p>
    </div>

    <!-- Action buttons -->
    <div>
        <div class="flex flex-col space-y-3 animate-slide-in">
            <Button
                as="a"
                :href="route('login.apps.redirect', 'portal')"
                class="w-full"
                size="lg"
            >
                {{ $trans('choose_login_with_existing_account') }}
            </Button>
            
            <Button
                as="a"
                :href="route('auth.register.view')"
                variant="outline"
                class="w-full"
                size="lg"
            >
                {{ $trans('choose_create_new_account') }}
            </Button>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import Logo from '@/components/Logo.vue'
import { Button } from '@/components/ui/button'
import AuthLayout from '@Shared/layouts/AuthLayout.vue'

defineOptions({
    layout: AuthLayout
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
