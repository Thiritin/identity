<template>
    <div class="space-y-6">
        <form @submit.prevent="submit" class="space-y-6">
            <!-- Email/Username input -->
            <div class="space-y-2">
                <label for="identifier" class="block text-sm font-medium text-gray-700">
                    Email or Username
                </label>
                <div class="relative">
                    <InputText
                        id="identifier"
                        v-model="form.identifier"
                        type="text"
                        autocomplete="username"
                        required
                        placeholder="Enter your email or username"
                        :class="{ 'p-invalid': form.errors.identifier }"
                        class="w-full p-inputtext-lg"
                    />
                    <ProgressSpinner v-if="form.processing" class="absolute right-3 top-3" style="width:24px;height:24px" />
                </div>
                <InlineMessage v-if="form.errors.identifier" severity="error">{{ form.errors.identifier }}</InlineMessage>
            </div>

            <!-- Continue button -->
            <div class="space-y-4">
                <Button
                    type="submit"
                    :loading="form.processing"
                    :disabled="form.processing || !form.identifier"
                    class="w-full p-button-lg"
                    :label="form.processing ? 'Checking...' : 'Continue'"
                />

                <!-- Passwordless login hint -->
                <Message v-if="showPasswordlessHint" severity="info" class="w-full">
                    <template #messageicon>
                        <i class="pi pi-key mr-2"></i>
                    </template>
                    Got a passkey? Sign in without a password
                </Message>
            </div>
        </form>

        <!-- Divider -->
        <Divider align="center">
            <span class="text-gray-500">or</span>
        </Divider>

        <!-- Alternative authentication methods -->
        <div class="space-y-3">
            <!-- Telegram Sign-in (placeholder) -->
            <Button
                class="w-full"
                severity="secondary"
                outlined
                disabled
            >
                <template #icon>
                    <Telegram class="w-5 h-5 mr-2" />
                </template>
                <span>Continue with Telegram (Coming Soon)</span>
            </Button>

            <!-- Help section -->
            <div class="text-center pt-2">
                <Button
                    type="button"
                    class="p-button-link p-button-lg"
                    label="Can't access your account?"
                    @click="showForgotAccountModal = true"
                    link
                />
            </div>
        </div>

        <!-- Forgot Account Dialog -->
        <Dialog
            v-model:visible="showForgotAccountModal"
            modal
            header="Need help signing in?"
            :style="{ width: '450px' }"
            :breakpoints="{ '960px': '75vw', '641px': '90vw' }"
        >
            <div class="text-center mb-6">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
                    <i class="pi pi-question-circle text-blue-600 text-xl"></i>
                </div>
                <p class="text-sm text-gray-600">
                    If you can't remember your email or username, you can recover your account or get help from our support team.
                </p>
            </div>
            <template #footer>
                <div class="flex justify-between w-full">
                    <Button
                        label="Cancel"
                        @click="showForgotAccountModal = false"
                        class="p-button-outlined"
                    />
                    <Link
                        :href="route('auth.forgot-password.view')"
                        class="no-underline"
                    >
                        <Button
                            label="Get Help"
                            class="p-button-primary"
                        />
                    </Link>
                </div>
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import ProgressSpinner from 'primevue/progressspinner'
import InlineMessage from 'primevue/inlinemessage'
import Message from 'primevue/message'
import Divider from 'primevue/divider'
import Dialog from 'primevue/dialog'
import Telegram from '@Shared/Components/Icons/Telegram.vue'

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
    form.post(route('auth.login.identify'), {
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
}

:deep(.p-message) {
    border-radius: 0.75rem;
    margin: 0;
}

:deep(.p-dialog) {
    border-radius: 1rem;
    
    .p-dialog-header {
        border-top-left-radius: 1rem;
        border-top-right-radius: 1rem;
        padding: 1.5rem;
    }
    
    .p-dialog-content {
        padding: 1.5rem;
    }
    
    .p-dialog-footer {
        border-bottom-left-radius: 1rem;
        border-bottom-right-radius: 1rem;
        padding: 1.5rem;
    }
}

:deep(.p-divider) {
    .p-divider-content {
        background-color: #ffffff;
    }
}
</style>