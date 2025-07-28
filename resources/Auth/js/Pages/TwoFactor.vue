<script setup>

import {computed, ref} from "vue";
import { Head } from "@inertiajs/vue3"
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import Logo from '@Auth/Pages/Logo.vue'
import AuthLayout from "@Shared/Layouts/AuthLayout.vue"
import { useForm } from 'laravel-precognition-vue-inertia'

defineOptions({
    layout: AuthLayout
})

const props = defineProps({
    lastUsedMethod: String,
    twoFactors: Array,
    submitFormUrl: String,
})

const form = useForm('post', props.submitFormUrl, {
    code: '',
    method: props.lastUsedMethod
})

const availableMethodTypes = computed(() => {
    return props.twoFactors.map(twoFactor => twoFactor.type)
})

const selectedMethod = ref(props.lastUsedMethod)

const selectedMethodName = computed(() => {
    return selectedMethod.value === 'totp' ? 'TOTP' : 'Yubikey OTP'
})

const otherMethodName = computed(() => {
    return selectedMethod.value === 'totp' ? 'Yubikey OTP' : 'TOTP'
})

const otherMethodType = computed(() => {
    return selectedMethod.value === 'totp' ? 'yubikey' : 'totp'
})

const otherMethodAvailable = computed(() => {
    return availableMethodTypes.value.includes(otherMethodType.value.toLowerCase())
})

const handleOtpInput = (event, position) => {
    const value = event.target.value
    let newCode = form.code.split('')
    
    // Update the current position
    newCode[position - 1] = value.slice(-1)
    
    // Move to next input if value entered
    if (value && position < 6) {
        event.target.nextElementSibling?.focus()
    }
    
    form.code = newCode.join('')
}

const submitForm = () => {
    form.post(props.submitFormUrl)
}

const toggleMethod = () => {
    selectedMethod.value = selectedMethod.value === 'totp' ? 'yubikey' : 'totp'
    form.method = selectedMethod.value
    form.code = ''
}
</script>

<template>
    <Head title="Two Factor Authentication"></Head>
    
    <!-- Header section -->
    <div class="px-8 pt-8 pb-6 text-center">
        <Logo class="mx-auto w-16 h-16 mb-4" />
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Two Factor Authentication</h1>
        <p class="text-gray-600">One more step to secure your account</p>
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

        <form @submit.prevent="submitForm" class="space-y-6 animate-slide-in">
            <!-- Code input -->
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ selectedMethodName }} Code
                </label>
                
                <template v-if="selectedMethodName !== 'TOTP'">
                    <input
                        id="code"
                        type="text"
                        v-model.trim.lazy="form.code"
                        autocomplete="one-time-code"
                        :class="{'border-red-300': form.invalid('code')}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-ef-green-primary focus:border-ef-green-primary transition-colors"
                    />
                </template>
                
                <template v-else>
                    <div class="flex justify-between gap-2">
                        <input
                            v-for="i in 6"
                            :key="i"
                            type="text"
                            maxlength="1"
                            :value="form.code[i-1] || ''"
                            @input="(e) => handleOtpInput(e, i)"
                            class="w-12 h-12 text-center border rounded-lg focus:ring-ef-green-primary focus:border-ef-green-primary transition-colors"
                            :class="{'border-red-300': form.invalid('code')}"
                        />
                    </div>
                </template>

                <p v-if="form.invalid('code')" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</p>
            </div>

            <!-- Action buttons -->
            <div class="flex flex-col space-y-3 pt-6">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-ef-green-primary hover:bg-ef-green-dark disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-medium py-3 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-ef-green-primary focus:ring-offset-2"
                >
                    <span v-if="form.processing">Processing...</span>
                    <span v-else>{{ $trans('login') }}</span>
                </button>
                
                <button
                    v-if="otherMethodAvailable"
                    type="button"
                    @click="toggleMethod"
                    class="w-full bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 font-medium py-3 px-4 rounded-lg transition-colors text-center"
                >
                    Switch to {{ otherMethodName }}
                </button>
            </div>
        </form>
    </div>
</template>

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
