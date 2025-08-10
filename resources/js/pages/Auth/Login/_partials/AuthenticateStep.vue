<template>
    <div class="space-y-6">
        <!-- User info header -->
        <Alert variant="success" class="w-full">
            <CheckCircleIcon class="h-4 w-4" />
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <img v-if="user?.avatar" :src="user.avatar" :alt="user?.name"
                            class="w-12 h-12 rounded-full object-cover" />
                        <div v-else class="w-12 h-12 rounded-full bg-ef-green-primary flex items-center justify-center">
                            <UserIcon class="w-6 h-6 text-white" />
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ user?.name }}</p>
                        <p class="text-sm text-gray-600 truncate">{{ user?.email }}</p>
                    </div>
                </div>
                <Button @click="$emit('go-back')" variant="ghost" size="sm">
                    Switch
                </Button>
            </div>
        </Alert>

        <!-- Authentication methods -->
        <div class="space-y-4">
            <!-- WebAuthn / Passkey authentication -->
            <div v-if="authMethods?.webauthn" class="space-y-4">
                <div class="text-center">
                    <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <KeyIcon class="text-blue-600 w-6 h-6" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                        Use your passkey
                    </h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Touch your security key or use biometrics
                    </p>
                </div>

                <Button @click="authenticateWithWebAuthn" :disabled="webauthnProcessing" class="w-full" size="lg">
                    <SpinnerIcon v-if="webauthnProcessing" class="w-4 h-4 mr-2 animate-spin" />
                    <KeyIcon v-else class="w-4 h-4 mr-2" />
                    Continue with passkey
                </Button>

                <Alert v-if="webauthnError" variant="destructive" class="w-full">
                    <ExclamationTriangleIcon class="h-4 w-4" />
                    <AlertDescription>{{ webauthnError }}</AlertDescription>
                </Alert>

                <!-- Separator -->
                <div v-if="authMethods?.password" class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <span class="w-full border-t" />
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="bg-background px-2 text-muted-foreground">or</span>
                    </div>
                </div>
            </div>

            <!-- Password authentication -->
            <div v-if="authMethods?.password">
                <form @submit.prevent="authenticateWithPassword" class="space-y-4">
                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Password
                        </label>
                        <div class="relative">
                            <Input v-model="passwordForm.password" :type="showPassword ? 'text' : 'password'"
                                id="password" placeholder="Enter your password"
                                :class="{ 'border-red-500': passwordForm.errors.password }" class="w-full pr-10" />
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600">
                                <EyeIcon v-if="!showPassword" class="h-4 w-4" />
                                <EyeSlashIcon v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <Alert v-if="passwordForm.errors.password" variant="destructive" class="mt-2">
                            <ExclamationTriangleIcon class="h-4 w-4" />
                            <AlertDescription>{{ passwordForm.errors.password }}</AlertDescription>
                        </Alert>
                    </div>

                    <!-- Stay signed in -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <Checkbox v-model:checked="passwordForm.stay_signed_in" id="stay_signed_in" />
                            <label for="stay_signed_in" class="text-sm text-gray-700">Stay signed in</label>
                        </div>

                        <Link :href="route('auth.forgot-password.view')" class="no-underline text-xs">
                        Forgot password?
                        </Link>
                    </div>

                    <Button type="submit" :disabled="passwordForm.processing || !passwordForm.password" class="w-full">
                        <SpinnerIcon v-if="passwordForm.processing" class="w-4 h-4 mr-2 animate-spin" />
                        {{ passwordForm.processing ? 'Signing in...' : 'Sign in' }}
                    </Button>
                </form>
            </div>

            <!-- No authentication methods available -->
            <div v-if="!authMethods?.password && !authMethods?.webauthn" class="text-center py-8">
                <div class="mx-auto w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-4">
                    <ExclamationTriangleIcon class="text-yellow-600 w-6 h-6" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                    No authentication method available
                </h3>
                <p class="text-sm text-gray-600">
                    Please contact support to set up authentication for your account.
                </p>
            </div>
        </div>

        <!-- Stay signed in explanation -->
        <Alert v-if="passwordForm.stay_signed_in || webauthnForm.stay_signed_in" variant="info" class="w-full">
            <CheckCircleIcon class="h-4 w-4" />
            <AlertDescription>
                <strong>Stay signed in</strong> keeps you signed in for 30 days on this device.
                Only use this on devices you trust.
            </AlertDescription>
        </Alert>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Checkbox } from '@/components/ui/checkbox'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Eye as EyeIcon, EyeOff as EyeSlashIcon, Key as KeyIcon, TriangleAlert as ExclamationTriangleIcon, CheckCircle as CheckCircleIcon, User as UserIcon, Loader2 as SpinnerIcon } from 'lucide-vue-next'

const props = defineProps({
    user: Object,
    authMethods: Object,
    webauthnOptions: Object
})

const emit = defineEmits(['authenticated', 'go-back'])

const webauthnProcessing = ref(false)
const webauthnError = ref('')
const showPassword = ref(false)

const passwordForm = useForm({
    password: '',
    stay_signed_in: false
})

const webauthnForm = useForm({
    credential: null,
    stay_signed_in: false
})

const authenticateWithPassword = () => {
    passwordForm.post(route('auth.login.authenticate-password'), {
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

        webauthnForm.post(route('auth.login.authenticate-webauthn'), {
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
.bg-ef-green-primary {
    background-color: #1a5f3f;
}

.bg-ef-green-dark {
    background-color: #0f3d27;
}

.text-ef-green-primary {
    color: #1a5f3f;
}

.hover\:text-ef-green-primary:hover {
    color: #1a5f3f;
}

.hover\:bg-ef-green-dark:hover {
    background-color: #0f3d27;
}

.focus\:ring-ef-green-primary:focus {
    --tw-ring-color: rgba(26, 95, 63, 0.5);
}
</style>