<template>
    <Head title="Reset Password"></Head>
    
    <!-- Header section -->
    <div class="px-8 pt-8 pb-6 text-center">
        <Logo class="mx-auto w-16 h-16 mb-4" />
        <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $trans('forgot_password_reset_title') }}</h1>
        <p class="text-gray-600">{{ $trans('forgot_password_reset_sub_title') }}</p>
    </div>

    <!-- Form section -->
    <div>
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

        <!-- Success status -->
        <div v-if="status" class="animate-slide-in">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <CheckCircleIcon class="h-5 w-5 text-green-500" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">{{ status }}</p>
                    </div>
                </div>
            </div>
        </div>

        <form v-else @submit.prevent="submit" class="space-y-6 animate-slide-in">
            <!-- Help text -->
            <div class="bg-blue-50 border-l-4 border-ef-green-primary rounded-lg p-4">
                <p class="text-sm text-gray-700">
                    {{ $trans('forgot_password_helptext') }}
                </p>
            </div>

            <!-- Email field -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $trans('email') }}
                </label>
                <input
                    id="email"
                    type="email"
                    v-model.trim.lazy="form.email"
                    @change="form.validate('email')"
                    autocomplete="email"
                    :class="{'border-red-300': form.invalid('email')}"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-ef-green-primary focus:border-ef-green-primary transition-colors"
                />
                <p v-if="form.invalid('email')" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>

            <!-- Action buttons -->
            <div class="flex flex-col space-y-3 pt-6">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-ef-green-primary hover:bg-ef-green-dark disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-medium py-3 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-ef-green-primary focus:ring-offset-2"
                >
                    <span v-if="form.processing">Processing...</span>
                    <span v-else>{{ $trans('send_reset_mail') }}</span>
                </button>
                
                <Link
                    :href="route('auth.login.view')"
                    class="w-full bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 font-medium py-3 px-4 rounded-lg transition-colors text-center"
                >
                    {{ $trans('back_to_login') }}
                </Link>
            </div>
        </form>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { TriangleAlert as ExclamationTriangleIcon, CheckCircle as CheckCircleIcon } from 'lucide-vue-next'
import Logo from '../components/Logo.vue'
import AuthLayout from '@Shared/layouts/AuthLayout.vue'
import { useForm } from 'laravel-precognition-vue-inertia'

defineOptions({
    layout: AuthLayout
})

const props = defineProps({
    status: String,
    errors: Object,
    canSeeLogin: Boolean,
})

const form = useForm('post', route('auth.forgot-password.store'), {
    email: null,
})

function submit() {
    form.submit()
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
