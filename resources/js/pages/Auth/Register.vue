<template>
    <Head title="Register"></Head>
    
    <!-- Header section -->
    <div class="px-8 pt-8 pb-6 text-center">
        <Logo class="mx-auto w-16 h-16 mb-4" />
        <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $trans('register_title') }}</h1>
        <p class="text-gray-600">{{ $trans('register_subtitle') }}</p>
    </div>

    <!-- Form section -->
    <div class="px-8 pb-8">
        <!-- Error display -->
        <Alert v-if="$page.props.flash?.error" variant="destructive" class="mb-6 animate-slide-in">
            <ExclamationTriangleIcon class="h-4 w-4" />
            <AlertDescription>{{ $page.props.flash.error }}</AlertDescription>
        </Alert>

        <form @submit.prevent="submit" class="space-y-6 animate-slide-in">
            <!-- Username field -->
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $trans('username') }}
                </label>
                <Input
                    id="username"
                    type="text"
                    v-model="form.username"
                    @change="form.validate('username')"
                    :class="{'border-red-500': form.invalid('username')}"
                    class="w-full"
                />
                <p v-if="form.invalid('username')" class="mt-1 text-sm text-red-600">{{ form.errors.username }}</p>
            </div>

            <!-- Email field -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $trans('email') }}
                </label>
                <Input
                    id="email"
                    type="email"
                    v-model="form.email"
                    @change="form.validate('email')"
                    placeholder="me@example.org"
                    autocomplete="email"
                    :class="{'border-red-500': form.invalid('email')}"
                    class="w-full"
                />
                <p v-if="form.invalid('email')" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>

            <!-- Password field -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $trans('password') }}
                </label>
                <Input
                    id="password"
                    type="password"
                    v-model="form.password"
                    @change="form.validate('password')"
                    autocomplete="new-password"
                    :class="{'border-red-500': form.invalid('password')}"
                    class="w-full"
                />
                <p v-if="form.invalid('password')" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
            </div>

            <PasswordInfoBox
                :password="form.password"
                class="bg-gray-50 rounded-lg p-4"
            />

            <!-- Password Confirmation field -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $trans('password_confirmation') }}
                </label>
                <Input
                    id="password_confirmation"
                    type="password"
                    v-model="form.password_confirmation"
                    @change="form.validate('password_confirmation')"
                    autocomplete="new-password"
                    :class="{'border-red-500': form.invalid('password_confirmation')}"
                    class="w-full"
                />
                <p v-if="form.invalid('password_confirmation')" class="mt-1 text-sm text-red-600">
                    {{ form.errors.password_confirmation }}
                </p>
            </div>

            <!-- Action buttons -->
            <div class="flex flex-col space-y-3 pt-6">
                <Button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full"
                    size="lg"
                >
                    <span v-if="form.processing">Processing...</span>
                    <span v-else>{{ $trans('register_button') }}</span>
                </Button>
                
                <Button
                    as="a"
                    :href="route('auth.login.view')"
                    variant="outline"
                    class="w-full"
                    size="lg"
                >
                    {{ $trans('register_back_to_login') }}
                </Button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { TriangleAlert as ExclamationTriangleIcon } from 'lucide-vue-next'
import Logo from '@/components/Logo.vue'
import PasswordInfoBox from '../PasswordInfoBox.vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Alert, AlertDescription } from '@/components/ui/alert'
import AuthLayout from '@Shared/layouts/AuthLayout.vue'
import { useForm } from 'laravel-precognition-vue-inertia'

defineOptions({
    layout: AuthLayout
})

const props = defineProps({
    errors: Object
})

const form = useForm('post', route('auth.register.store'), {
    email: null,
    username: null,
    password: null,
    password_confirmation: null,
})

function submit() {
    form.submit()
}
</script>

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
