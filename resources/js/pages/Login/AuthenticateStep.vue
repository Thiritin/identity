<template>
    <div class="space-y-6">
        <!-- User info header -->
        <Message class="w-full" severity="success" :closable="false">
            <template #messageicon>
                <Avatar 
                    v-if="user?.avatar" 
                    :image="user.avatar" 
                    :label="user?.name?.charAt(0)?.toUpperCase() || '?'"
                    size="large"
                    shape="circle"
                    class="mr-3"
                />
                <Avatar 
                    v-else
                    :label="user?.name?.charAt(0)?.toUpperCase() || '?'"
                    size="large"
                    shape="circle"
                    class="mr-3"
                />
            </template>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 truncate">{{ user?.name }}</p>
                <p class="text-sm text-gray-600 truncate">{{ user?.email }}</p>
            </div>
            <template #messageactions>
                <Button
                    @click="$emit('go-back')"
                    label="Switch"
                    class="p-button-text p-button-sm"
                />
            </template>
        </Message>

        <!-- Authentication methods -->
        <div class="space-y-4">
            <!-- WebAuthn / Passkey authentication -->
            <div v-if="authMethods?.webauthn" class="space-y-4">
                <div class="text-center">
                    <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <i class="pi pi-key text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                        Use your passkey
                    </h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Touch your security key or use biometrics
                    </p>
                </div>

                <Button
                    @click="authenticateWithWebAuthn"
                    :loading="webauthnProcessing"
                    :disabled="webauthnProcessing"
                    class="w-full p-button-lg"
                >
                    <template #icon>
                        <i class="pi pi-key mr-2"></i>
                    </template>
                    Continue with passkey
                </Button>

                <Message v-if="webauthnError" severity="error" class="w-full">
                    {{ webauthnError }}
                </Message>

                <!-- Separator -->
                <Divider v-if="authMethods?.password" align="center">
                    <span class="text-gray-500">or</span>
                </Divider>
            </div>

            <!-- Password authentication -->
            <div v-if="authMethods?.password">
                <form @submit.prevent="authenticateWithPassword" class="space-y-4">
                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Password
                        </label>
                        <div class="relative">
                            <Password
                                v-model="passwordForm.password"
                                :feedback="false"
                                :toggleMask="true"
                                inputClass="w-full"
                                class="w-full"
                                :class="{ 'p-invalid': passwordForm.errors.password }"
                                inputId="password"
                                placeholder="Enter your password"
                                :pt="{
                                    input: { class: 'w-full p-inputtext-lg' }
                                }"
                            />
                        </div>
                        <InlineMessage v-if="passwordForm.errors.password" severity="error">
                            {{ passwordForm.errors.password }}
                        </InlineMessage>
                    </div>

                    <!-- Stay signed in -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <Checkbox
                                v-model="passwordForm.stay_signed_in"
                                :binary="true"
                                inputId="stay_signed_in"
                            />
                            <label for="stay_signed_in" class="ml-2 text-sm text-gray-700">Stay signed in</label>
                        </div>
                        
                        <Link
                            :href="route('auth.forgot-password.view')"
                            class="no-underline"
                        >
                            <Button
                                label="Forgot password?"
                                link
                                text
                                class="p-button-link p-button-sm"
                            />
                        </Link>
                    </div>

                    <Button
                        type="submit"
                        :loading="passwordForm.processing"
                        :disabled="passwordForm.processing || !passwordForm.password"
                        class="w-full"
                        :label="passwordForm.processing ? 'Signing in...' : 'Sign in'"
                    />
                </form>
            </div>

            <!-- No authentication methods available -->
            <div v-if="!authMethods?.password && !authMethods?.webauthn" class="text-center py-8">
                <div class="mx-auto w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-4">
                    <i class="pi pi-exclamation-triangle text-yellow-600 text-2xl"></i>
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
        <Message v-if="passwordForm.stay_signed_in || webauthnForm.stay_signed_in" severity="info" class="w-full">
            <template #messageicon>
                <i class="pi pi-info-circle"></i>
            </template>
            <span class="text-sm">
                <strong>Stay signed in</strong> keeps you signed in for 30 days on this device. 
                Only use this on devices you trust.
            </span>
        </Message>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import Button from '@Shared/components/volt/Button.vue'
import Password from '@Shared/components/volt/Password.vue'
import Checkbox from '@Shared/components/volt/Checkbox.vue'
import Message from '@Shared/components/volt/Message.vue'
import InlineMessage from '@Shared/components/volt/Message.vue'
import Divider from '@Shared/components/volt/Divider.vue'
import Avatar from '@Shared/components/volt/Avatar.vue'

const props = defineProps({
    user: Object,
    authMethods: Object,
    webauthnOptions: Object
})

const emit = defineEmits(['authenticated', 'go-back'])

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

<style lang="scss">
// Override PrimeVue styles to match our design
:deep(.p-inputtext) {
    border-radius: 0.75rem;
    font-size: 16px;
    padding: 0.75rem 1rem;
    border-width: 1.5px;
    
    &:enabled:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    &.p-invalid {
        border-color: #ef4444;
        
        &:enabled:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }
    }
}

:deep(.p-password) {
    width: 100%;
    
    input {
        width: 100%;
    }
}

:deep(.p-button) {
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
    
    &:not(:disabled):hover {
        transform: translateY(-1px);
    }
    
    &:not(:disabled):active {
        transform: translateY(0);
    }
    
    &.p-button-lg {
        font-size: 1rem;
    }
    
    &.p-button-sm {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }
}

:deep(.p-message) {
    border-radius: 0.75rem;
    margin: 0;
}

:deep(.p-checkbox) {
    width: 1.25rem;
    height: 1.25rem;
    
    .p-checkbox-box {
        border-radius: 0.375rem;
        
        &.p-highlight {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }
        
        &:not(.p-disabled):hover {
            border-color: #3b82f6;
        }
    }
}

:deep(.p-divider) {
    .p-divider-content {
        background-color: #ffffff;
    }
}

:deep(.p-avatar) {
    &.p-avatar-lg {
        width: 3rem;
        height: 3rem;
        font-size: 1.5rem;
    }
}
</style>