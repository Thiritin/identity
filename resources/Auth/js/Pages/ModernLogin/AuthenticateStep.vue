<template>
    <div class="space-y-6">
        <!-- User info header -->
        <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
            <div class="flex-shrink-0">
                <img 
                    v-if="user?.avatar" 
                    :src="user.avatar" 
                    :alt="user.name"
                    class="w-12 h-12 rounded-full object-cover"
                />
                <div v-else class="w-12 h-12 rounded-full bg-ef-green-primary flex items-center justify-center">
                    <span class="text-white font-medium text-lg">
                        {{ user?.name?.charAt(0)?.toUpperCase() || '?' }}
                    </span>
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-text-primary truncate">{{ user?.name }}</p>
                <p class="text-sm text-text-secondary truncate">{{ user?.email }}</p>
            </div>
            <button
                @click="$emit('go-back')"
                class="text-sm text-ef-green-primary hover:text-ef-green-dark font-medium"
            >
                Switch account
            </button>
        </div>

        <!-- Authentication methods -->
        <div class="space-y-4">
            <!-- WebAuthn / Passkey authentication -->
            <div v-if="authMethods?.webauthn" class="space-y-4">
                <div class="text-center">
                    <h3 class="text-lg font-medium text-text-primary mb-2">
                        Use your security key
                    </h3>
                    <p class="text-sm text-text-secondary mb-4">
                        Touch your security key, or use biometrics to sign in
                    </p>
                </div>

                <button
                    @click="authenticateWithWebAuthn"
                    :disabled="webauthnProcessing"
                    class="w-full flex items-center justify-center px-4 py-3 bg-ef-green-primary hover:bg-ef-green-dark disabled:bg-gray-300 text-white rounded-lg transition-colors"
                >
                    <KeyIcon class="w-5 h-5 mr-3" />
                    <span v-if="webauthnProcessing">Waiting for security key...</span>
                    <span v-else>Use security key</span>
                </button>

                <div v-if="webauthnError" class="text-sm text-red-600 text-center">
                    {{ webauthnError }}
                </div>

                <!-- Separator -->
                <div v-if="authMethods?.password" class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-text-secondary">or</span>
                    </div>
                </div>
            </div>

            <!-- Password authentication -->
            <div v-if="authMethods?.password">
                <form @submit.prevent="authenticateWithPassword" class="space-y-4">
                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-medium text-text-primary">
                            Password
                        </label>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="passwordForm.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                required
                                class="modern-input w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ef-green-primary focus:border-ef-green-primary transition-colors"
                                :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': passwordForm.errors.password }"
                                placeholder="Enter your password"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-3 text-gray-400 hover:text-gray-600"
                            >
                                <EyeIcon v-if="!showPassword" class="w-6 h-6" />
                                <EyeSlashIcon v-else class="w-6 h-6" />
                            </button>
                        </div>
                        <div v-if="passwordForm.errors.password" class="text-sm text-red-600">
                            {{ passwordForm.errors.password }}
                        </div>
                    </div>

                    <!-- Stay signed in -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input
                                v-model="passwordForm.stay_signed_in"
                                type="checkbox"
                                class="rounded border-gray-300 text-ef-green-primary focus:ring-ef-green-primary"
                            />
                            <span class="ml-2 text-sm text-text-primary">Stay signed in</span>
                        </label>
                        
                        <Link
                            :href="route('auth.forgot-password.view')"
                            class="text-sm text-ef-green-primary hover:text-ef-green-dark font-medium"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        :disabled="passwordForm.processing || !passwordForm.password"
                        class="w-full bg-ef-green-primary hover:bg-ef-green-dark disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-medium py-3 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-ef-green-primary focus:ring-offset-2"
                    >
                        <span v-if="passwordForm.processing">Signing in...</span>
                        <span v-else>Sign in</span>
                    </button>
                </form>
            </div>

            <!-- No authentication methods available -->
            <div v-if="!authMethods?.password && !authMethods?.webauthn" class="text-center py-8">
                <ExclamationTriangleIcon class="w-12 h-12 text-yellow-500 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-text-primary mb-2">
                    No authentication method available
                </h3>
                <p class="text-sm text-text-secondary">
                    Please contact support to set up authentication for your account.
                </p>
            </div>
        </div>

        <!-- Stay signed in explanation -->
        <div v-if="passwordForm.stay_signed_in || webauthnForm.stay_signed_in" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
                <InformationCircleIcon class="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" />
                <div class="ml-3">
                    <p class="text-sm text-blue-800">
                        <strong>Stay signed in</strong> keeps you signed in for 30 days on this device. 
                        Only use this on devices you trust.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import { 
    KeyIcon, 
    EyeIcon, 
    EyeSlashIcon, 
    ExclamationTriangleIcon,
    InformationCircleIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
    user: Object,
    authMethods: Object,
    webauthnOptions: Object
})

const emit = defineEmits(['authenticated', 'go-back'])

const showPassword = ref(false)
const webauthnProcessing = ref(false)
const webauthnError = ref('')

const passwordForm = useForm({
    password: '',
    stay_signed_in: false
})

const webauthnForm = useForm({
    credential: null,
    stay_signed_in: false
})

const authenticateWithPassword = () => {
    passwordForm.post(route('auth.modern-login.authenticate-password'), {
        onSuccess: () => {
            emit('authenticated')
        }
    })
}

const authenticateWithWebAuthn = async () => {
    webauthnProcessing.value = true
    webauthnError.value = ''

    try {
        if (!props.webauthnOptions) {
            throw new Error('WebAuthn options not available')
        }

        // Convert base64 challenge to ArrayBuffer
        const challenge = Uint8Array.from(atob(props.webauthnOptions.challenge), c => c.charCodeAt(0))
        
        // Convert allowed credentials
        const allowCredentials = props.webauthnOptions.allowCredentials?.map(cred => ({
            type: cred.type,
            id: Uint8Array.from(atob(cred.id), c => c.charCodeAt(0)),
            transports: cred.transports
        })) || []

        const publicKeyCredentialRequestOptions = {
            challenge,
            allowCredentials,
            userVerification: props.webauthnOptions.userVerification || 'preferred',
            timeout: props.webauthnOptions.timeout || 60000
        }

        const credential = await navigator.credentials.get({
            publicKey: publicKeyCredentialRequestOptions
        })

        if (!credential) {
            throw new Error('No credential returned')
        }

        // Convert credential to format expected by server
        const credentialData = {
            id: credential.id,
            rawId: btoa(String.fromCharCode(...new Uint8Array(credential.rawId))),
            type: credential.type,
            response: {
                authenticatorData: btoa(String.fromCharCode(...new Uint8Array(credential.response.authenticatorData))),
                clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(credential.response.clientDataJSON))),
                signature: btoa(String.fromCharCode(...new Uint8Array(credential.response.signature))),
                userHandle: credential.response.userHandle ? btoa(String.fromCharCode(...new Uint8Array(credential.response.userHandle))) : null
            }
        }

        webauthnForm.credential = credentialData
        
        webauthnForm.post(route('auth.modern-login.authenticate-webauthn'), {
            onSuccess: () => {
                emit('authenticated')
            },
            onError: (errors) => {
                webauthnError.value = errors.webauthn || 'Authentication failed'
            }
        })

    } catch (error) {
        console.error('WebAuthn authentication failed:', error)
        
        if (error.name === 'NotAllowedError') {
            webauthnError.value = 'Authentication was cancelled or timed out'
        } else if (error.name === 'NotSupportedError') {
            webauthnError.value = 'WebAuthn is not supported by your browser'
        } else if (error.name === 'SecurityError') {
            webauthnError.value = 'Security error during authentication'
        } else {
            webauthnError.value = 'Authentication failed. Please try again.'
        }
    } finally {
        webauthnProcessing.value = false
    }
}

// Auto-trigger WebAuthn if it's the only available method
onMounted(() => {
    if (props.authMethods?.webauthn && !props.authMethods?.password) {
        setTimeout(() => {
            authenticateWithWebAuthn()
        }, 500)
    }
})
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