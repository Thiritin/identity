<template>
    <Head title="Passkeys & Security Keys"></Head>
    
    <div class="max-w-4xl mx-auto p-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Passkeys & Security Keys</h1>
            <p class="text-gray-600">
                Passkeys and security keys provide secure, passwordless authentication using biometrics or physical devices.
            </p>
        </div>

        <!-- Add new passkey section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-start space-x-4">
                <div class="shrink-0">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <KeyIcon class="w-6 h-6 text-green-600" />
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Add a new passkey</h3>
                    <p class="text-gray-600 mb-4">
                        Passkeys are a secure replacement for passwords. They use your device's biometrics or PIN.
                    </p>
                    <button
                        @click="showAddPasskey = true"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                    >
                        <PlusIcon class="w-4 h-4 inline mr-2" />
                        Add Passkey
                    </button>
                </div>
            </div>
        </div>

        <!-- Existing passkeys -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Your Passkeys</h3>
                <p class="text-gray-600 text-sm mt-1">
                    Manage your registered passkeys and security keys
                </p>
            </div>

            <div v-if="!hasCredentials" class="p-6 text-center">
                <KeyIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 mb-2">No passkeys registered</h3>
                <p class="text-gray-600 mb-4">
                    Add your first passkey to enable secure, passwordless authentication.
                </p>
                <button
                    @click="showAddPasskey = true"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                >
                    Add Your First Passkey
                </button>
            </div>

            <div v-else class="divide-y divide-gray-200">
                <div
                    v-for="credential in credentials"
                    :key="credential.id"
                    class="p-6 flex items-center justify-between"
                >
                    <div class="flex items-center space-x-4">
                        <div class="shrink-0">
                            <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                                <KeyIcon class="w-5 h-5 text-gray-600" />
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">{{ credential.name }}</h4>
                            <div class="text-xs text-gray-500 space-y-1">
                                <p>Added {{ credential.created_at }}</p>
                                <p>Last used: {{ credential.last_used_at }}</p>
                                <p v-if="credential.sign_count > 0">
                                    Used {{ credential.sign_count }} time(s)
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button
                            @click="editCredential(credential)"
                            class="text-gray-400 hover:text-gray-600 p-2"
                            title="Rename"
                        >
                            <PencilIcon class="w-4 h-4" />
                        </button>
                        <button
                            @click="deleteCredential(credential)"
                            class="text-red-400 hover:text-red-600 p-2"
                            title="Remove"
                        >
                            <TrashIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Passkey Modal -->
        <div v-if="showAddPasskey" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Passkey</h3>
                
                <div v-if="!registrationInProgress">
                    <div class="space-y-4">
                        <div>
                            <label for="passkey-name" class="block text-sm font-medium text-gray-700 mb-2">
                                Name your passkey
                            </label>
                            <input
                                id="passkey-name"
                                v-model="newPasskeyName"
                                type="text"
                                placeholder="e.g., iPhone Touch ID, YubiKey"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            />
                        </div>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex">
                                <InformationCircleIcon class="w-5 h-5 text-blue-400 shrink-0" />
                                <div class="ml-3">
                                    <p class="text-sm text-blue-800">
                                        You'll be prompted to use your device's biometrics, PIN, or security key to create your passkey.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-4 border-green-500 border-t-transparent mx-auto mb-4"></div>
                    <h4 class="text-lg font-medium text-gray-900 mb-2">Follow the prompts</h4>
                    <p class="text-gray-600">
                        Complete the process on your device or security key
                    </p>
                </div>

                <div v-if="registrationError" class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex">
                        <ExclamationTriangleIcon class="w-5 h-5 text-red-400 shrink-0" />
                        <div class="ml-3">
                            <p class="text-sm text-red-800">{{ registrationError }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex space-x-3 mt-6">
                    <button
                        @click="closeAddPasskey"
                        :disabled="registrationInProgress"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50"
                    >
                        Cancel
                    </button>
                    <button
                        @click="registerPasskey"
                        :disabled="!newPasskeyName || registrationInProgress"
                        class="flex-1 bg-green-600 hover:bg-green-700 disabled:bg-gray-300 text-white px-4 py-2 rounded-lg font-medium"
                    >
                        <span v-if="registrationInProgress">Creating...</span>
                        <span v-else>Create Passkey</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit Credential Modal -->
        <div v-if="editingCredential" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Rename Passkey</h3>
                
                <div class="space-y-4">
                    <div>
                        <label for="edit-name" class="block text-sm font-medium text-gray-700 mb-2">
                            Passkey name
                        </label>
                        <input
                            id="edit-name"
                            v-model="editingCredential.name"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                        />
                    </div>
                </div>

                <div class="flex space-x-3 mt-6">
                    <button
                        @click="editingCredential = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        @click="updateCredential"
                        class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium"
                    >
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import {
    Key as KeyIcon,
    Plus as PlusIcon,
    Edit as PencilIcon,
    Trash as TrashIcon,
    Info as InformationCircleIcon,
    TriangleAlert as ExclamationTriangleIcon
} from 'lucide-vue-next'

const props = defineProps({
    credentials: Array,
    hasCredentials: Boolean
})

const showAddPasskey = ref(false)
const newPasskeyName = ref('')
const registrationInProgress = ref(false)
const registrationError = ref('')
const editingCredential = ref(null)

const registerPasskey = async () => {
    registrationInProgress.value = true
    registrationError.value = ''

    try {
        // Get registration options from server
        const optionsResponse = await fetch('/user/settings/security/passkeys/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })

        if (!optionsResponse.ok) {
            throw new Error('Failed to get registration options')
        }

        const options = await optionsResponse.json()

        // Convert options for WebAuthn API
        const publicKeyCredentialCreationOptions = {
            challenge: Uint8Array.from(atob(options.challenge), c => c.charCodeAt(0)),
            rp: options.rp,
            user: {
                ...options.user,
                id: Uint8Array.from(atob(options.user.id), c => c.charCodeAt(0))
            },
            pubKeyCredParams: options.pubKeyCredParams,
            excludeCredentials: options.excludeCredentials?.map(cred => ({
                ...cred,
                id: Uint8Array.from(atob(cred.id), c => c.charCodeAt(0))
            })),
            authenticatorSelection: options.authenticatorSelection,
            timeout: options.timeout
        }

        // Create credential
        const credential = await navigator.credentials.create({
            publicKey: publicKeyCredentialCreationOptions
        })

        if (!credential) {
            throw new Error('No credential created')
        }

        // Convert credential for server
        const credentialData = {
            id: credential.id,
            rawId: btoa(String.fromCharCode(...new Uint8Array(credential.rawId))),
            type: credential.type,
            response: {
                attestationObject: btoa(String.fromCharCode(...new Uint8Array(credential.response.attestationObject))),
                clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(credential.response.clientDataJSON)))
            },
            transports: credential.response.getTransports?.() || []
        }

        // Register with server
        const registerResponse = await fetch('/user/settings/security/passkeys', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                name: newPasskeyName.value,
                credential: credentialData
            })
        })

        if (!registerResponse.ok) {
            const error = await registerResponse.json()
            throw new Error(error.message || 'Registration failed')
        }

        // Success - reload page to show new credential
        router.reload()
        closeAddPasskey()

    } catch (error) {
        console.error('Passkey registration failed:', error)
        
        if (error.name === 'NotAllowedError') {
            registrationError.value = 'Registration was cancelled or timed out'
        } else if (error.name === 'NotSupportedError') {
            registrationError.value = 'Passkeys are not supported by your browser'
        } else if (error.name === 'SecurityError') {
            registrationError.value = 'Security error during registration'
        } else {
            registrationError.value = error.message || 'Registration failed. Please try again.'
        }
    } finally {
        registrationInProgress.value = false
    }
}

const closeAddPasskey = () => {
    showAddPasskey.value = false
    newPasskeyName.value = ''
    registrationInProgress.value = false
    registrationError.value = ''
}

const editCredential = (credential) => {
    editingCredential.value = { ...credential }
}

const updateCredential = async () => {
    try {
        await fetch(`/user/settings/security/passkeys/${editingCredential.value.id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                name: editingCredential.value.name
            })
        })

        router.reload()
        editingCredential.value = null
    } catch (error) {
        console.error('Failed to update credential:', error)
    }
}

const deleteCredential = async (credential) => {
    if (!confirm(`Are you sure you want to remove "${credential.name}"? This action cannot be undone.`)) {
        return
    }

    try {
        const response = await fetch(`/user/settings/security/passkeys/${credential.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })

        if (!response.ok) {
            const error = await response.json()
            alert(error.message || 'Failed to remove passkey')
            return
        }

        router.reload()
    } catch (error) {
        console.error('Failed to delete credential:', error)
        alert('Failed to remove passkey. Please try again.')
    }
}
</script>