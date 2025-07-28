<template>
    <div class="space-y-6">
        <form @submit.prevent="submit" class="space-y-6">
            <!-- Email input -->
            <div class="space-y-2">
                <label for="identifier" class="block text-sm font-medium text-text-primary">
                    Email address
                </label>
                <div class="relative">
                    <input
                        id="identifier"
                        v-model="form.identifier"
                        type="email"
                        autocomplete="email"
                        required
                        class="modern-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ef-green-primary focus:border-ef-green-primary transition-colors"
                        :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.identifier }"
                        placeholder="Enter your email"
                    />
                    <div v-if="form.processing" class="absolute right-3 top-3">
                        <div class="animate-spin rounded-full h-6 w-6 border-2 border-gray-300 border-t-ef-green-primary"></div>
                    </div>
                </div>
                <div v-if="form.errors.identifier" class="text-sm text-red-600">
                    {{ form.errors.identifier }}
                </div>
            </div>

            <!-- Action buttons -->
            <div class="space-y-4">
                <button
                    type="submit"
                    :disabled="form.processing || !form.identifier"
                    class="w-full bg-ef-green-primary hover:bg-ef-green-dark disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-medium py-3 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-ef-green-primary focus:ring-offset-2"
                >
                    <span v-if="form.processing">Checking...</span>
                    <span v-else>Next</span>
                </button>

                <div class="text-center">
                    <button
                        type="button"
                        class="text-sm text-ef-green-primary hover:text-ef-green-dark font-medium"
                        @click="showForgotPassword = true"
                    >
                        Forgot email?
                    </button>
                </div>
            </div>
        </form>

        <!-- Alternative sign-in methods -->
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-background-modern text-text-secondary">or</span>
            </div>
        </div>

        <div class="space-y-3">
            <!-- Future: Add social login buttons here -->
            <button
                type="button"
                class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
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
        </div>

        <!-- Forgot Password Modal -->
        <div v-if="showForgotPassword" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full">
                <h3 class="text-lg font-medium text-text-primary mb-4">Find your email</h3>
                <p class="text-sm text-text-secondary mb-4">
                    Enter your recovery email or contact support for help accessing your account.
                </p>
                <div class="flex space-x-3">
                    <button
                        @click="showForgotPassword = false"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <Link
                        :href="route('auth.forgot-password.view')"
                        class="flex-1 bg-ef-green-primary hover:bg-ef-green-dark text-white px-4 py-2 rounded-lg text-center"
                    >
                        Get Help
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'

const emit = defineEmits(['user-identified'])

const showForgotPassword = ref(false)

const form = useForm({
    identifier: '',
    login_challenge: new URLSearchParams(window.location.search).get('login_challenge')
})

const submit = () => {
    form.post(route('auth.modern-login.identify'), {
        onSuccess: () => {
            emit('user-identified', form.identifier)
        }
    })
}
</script>

<style scoped>
.modern-input {
    font-size: 16px; /* Prevent zoom on iOS */
    background-color: #ffffff;
}

.modern-input:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(26, 95, 63, 0.2);
}

.bg-background-modern {
    background-color: #fafbfc;
}

.text-text-primary {
    color: #202124;
}

.text-text-secondary {
    color: #5f6368;
}

.bg-ef-green-primary {
    background-color: #1a5f3f;
}

.bg-ef-green-dark {
    background-color: #0f3d27;
}

.text-ef-green-primary {
    color: #1a5f3f;
}

.text-ef-green-dark {
    color: #0f3d27;
}

.hover\:bg-ef-green-dark:hover {
    background-color: #0f3d27;
}

.focus\:ring-ef-green-primary:focus {
    --tw-ring-color: rgba(26, 95, 63, 0.5);
}

.focus\:border-ef-green-primary:focus {
    border-color: #1a5f3f;
}
</style>