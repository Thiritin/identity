<template>
    <div class="space-y-6">
        <form @submit.prevent="submit" class="space-y-6">
            <!-- Email/Username input -->
            <div class="space-y-2">
                <label for="identifier" class="block text-sm font-medium text-gray-700">
                    Email or Username
                </label>
                <div class="relative">
                    <Input
                        id="identifier"
                        v-model="form.identifier"
                        type="text"
                        autocomplete="username"
                        required
                        placeholder="Enter your email or username"
                        :class="{ 'border-red-500': form.errors.identifier }"
                        class="w-full"
                    />
                    <div v-if="form.processing" class="absolute right-3 top-3">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-gray-900"></div>
                    </div>
                </div>
                <Alert v-if="form.errors.identifier" variant="destructive" class="w-full">
                    <ExclamationTriangleIcon class="h-4 w-4" />
                    <AlertDescription>{{ form.errors.identifier }}</AlertDescription>
                </Alert>
            </div>

            <!-- Continue button -->
            <div class="space-y-4">
                <Button
                    type="submit"
                    :disabled="form.processing || !form.identifier"
                    class="w-full"
                >
                    <SpinnerIcon v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                    {{ form.processing ? 'Checking...' : 'Continue' }}
                </Button>
            </div>
        </form>

        <!-- Divider -->
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <span class="w-full border-t" />
            </div>
            <div class="relative flex justify-center text-xs uppercase">
                <span class="bg-background px-2 text-muted-foreground">or</span>
            </div>
        </div>

        <!-- Alternative authentication methods -->
        <div class="space-y-3">
            <!-- Telegram Sign-in (placeholder) -->
            <Button
                class="w-full"
                variant="outline"
            >
                <Telegram class="w-5 h-5 mr-2 fill-[#24A1DE]" />
                Sign in with Telegram
            </Button>
        </div>
    </div>
</template>

<script setup>
import {computed, onMounted, ref} from 'vue'
import {useForm} from '@inertiajs/vue3'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { TriangleAlert as ExclamationTriangleIcon, Loader2 as SpinnerIcon } from 'lucide-vue-next'
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
