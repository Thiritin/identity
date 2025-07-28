<template>
    <div class="space-y-6">
        <form @submit.prevent="submit" class="space-y-6">
            <!-- Email/Username input -->
            <div class="space-y-2">
                <label for="identifier" class="block text-sm font-medium text-gray-700">
                    Email or Username
                </label>
                <div class="relative">
                    <input
                        id="identifier"
                        v-model="form.identifier"
                        type="text"
                        autocomplete="username"
                        required
                        class="auth-input w-full px-4 py-3 border border-gray-300 rounded-xl focus-accent transition-all"
                        :class="{ 
                            'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.identifier,
                            'pr-12': form.processing 
                        }"
                        placeholder="Enter your email or username"
                    />
                    <div v-if="form.processing" class="absolute right-3 top-3">
                        <div class="animate-spin rounded-full h-6 w-6 border-2 border-gray-300 border-t-blue-600"></div>
                    </div>
                </div>
                <div v-if="form.errors.identifier" class="text-sm text-red-600">
                    {{ form.errors.identifier }}
                </div>
            </div>

            <!-- Continue button -->
            <div class="space-y-4">
                <button
                    type="submit"
                    :disabled="form.processing || !form.identifier"
                    class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-medium py-3 px-4 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform hover:scale-[1.02] disabled:hover:scale-100"
                >
                    <span v-if="form.processing" class="flex items-center justify-center">
                        <div class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent mr-2"></div>
                        Checking...
                    </span>
                    <span v-else>Continue</span>
                </button>

                <!-- Passwordless login hint -->
                <div v-if="showPasswordlessHint" class="text-center">
                    <p class="text-sm text-blue-600 bg-blue-50 rounded-lg p-3 border border-blue-200">
                        <span class="inline-flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z" clip-rule="evenodd"/>
                            </svg>
                            Got a passkey? Sign in without a password
                        </span>
                    </p>
                </div>
            </div>
        </form>

        <!-- Divider -->
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-3 bg-white text-gray-500">or</span>
            </div>
        </div>

        <!-- Alternative authentication methods -->
        <div class="space-y-3">
            <!-- Google Sign-in (placeholder) -->
            <button
                type="button"
                class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                disabled
            >
                <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                <span class="text-gray-500">Continue with Google (Coming Soon)</span>
            </button>

            <!-- Help section -->
            <div class="text-center pt-2">
                <button
                    type="button"
                    class="text-sm text-blue-600 hover:text-blue-500 font-medium transition-colors"
                    @click="showForgotAccountModal = true"
                >
                    Can't access your account?
                </button>
            </div>
        </div>

        <!-- Forgot Account Modal -->
        <div v-if="showForgotAccountModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50" @click="showForgotAccountModal = false">
            <div class="bg-white rounded-2xl p-6 max-w-md w-full animate-slide-in" @click.stop>
                <div class="text-center mb-6">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
                        <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Need help signing in?</h3>
                    <p class="text-sm text-gray-600">
                        If you can't remember your email or username, you can recover your account or get help from our support team.
                    </p>
                </div>
                <div class="flex space-x-3">
                    <button
                        @click="showForgotAccountModal = false"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors"
                    >
                        Cancel
                    </button>
                    <Link
                        :href="route('auth.forgot-password.view')"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-center transition-colors"
                    >
                        Get Help
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'

const emit = defineEmits(['user-identified'])

const showForgotAccountModal = ref(false)
const showPasswordlessHint = ref(false)

const form = useForm({
    identifier: '',
    login_challenge: new URLSearchParams(window.location.search).get('login_challenge')
})

// Show passwordless hint after user starts typing
const showHint = computed(() => {
    return form.identifier.length > 0 && !form.processing && !form.errors.identifier
})

// Animate hint appearance
setTimeout(() => {
    showPasswordlessHint.value = true
}, 2000)

const submit = () => {
    form.post(route('auth.modern-login.identify'), {
        onSuccess: () => {
            emit('user-identified', form.identifier)
        }
    })
}

onMounted(() => {
    // Focus the input field when component mounts
    document.getElementById('identifier')?.focus()
})
</script>

<style scoped>
.auth-input {
    font-size: 16px; /* Prevent zoom on iOS */
    background-color: #ffffff;
    border-width: 1.5px;
}

.auth-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.focus-accent:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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

/* Enhance button interactions */
button:not(:disabled):hover {
    transform: translateY(-1px);
}

button:not(:disabled):active {
    transform: translateY(0);
}
</style>