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
                        class="w-full"
                    />
                    <div v-if="form.processing" class="absolute right-3 top-3">
                        <ProgressBar mode="indeterminate" style="width:24px;height:24px" />
                    </div>
                </div>
                <Message v-if="form.errors.identifier" severity="error" class="w-full">
                    {{ form.errors.identifier }}
                </Message>
            </div>

            <!-- Continue button -->
            <div class="space-y-4">
                <Button
                    type="submit"
                    :loading="form.processing"
                    :disabled="form.processing || !form.identifier"
                    class="w-full"
                    :label="form.processing ? 'Checking...' : 'Continue'"
                />
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
                label="Sign in with Telegram"
                :outlined="true"
            >
                <template #icon>
                    <Telegram class="w-5 h-5 mr-2 fill-[#24A1DE]" />
                </template>
            </Button>
        </div>
    </div>
</template>

<script setup>
import {computed, onMounted, ref} from 'vue'
import {useForm} from '@inertiajs/vue3'
import InputText from '@Shared/components/volt/InputText.vue'
import Button from '@Shared/components/volt/Button.vue'
import ProgressBar from '@Shared/components/volt/ProgressBar.vue'
import Message from '@Shared/components/volt/Message.vue'
import Divider from '@Shared/components/volt/Divider.vue'
import Telegram from '@Shared/components/Icons/Telegram.vue'

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
/* No need for custom styles as they are handled by Tailwind and PrimeUI */
</style>
