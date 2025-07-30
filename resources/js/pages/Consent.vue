<template>
    <AuthLayout>
        <Head title="Grant Permission"></Head>
        
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="flex items-center justify-center space-x-4 mb-6">
                <!-- App logo or placeholder -->
                <div v-if="client.logo_uri" class="w-12 h-12 rounded-lg overflow-hidden">
                    <img :src="client.logo_uri" :alt="client.name" class="w-full h-full object-cover" />
                </div>
                <div v-else class="w-12 h-12 rounded-lg bg-gray-200 flex items-center justify-center">
                    <ApplicationIcon class="w-6 h-6 text-gray-500" />
                </div>
                
                <ArrowRightIcon class="w-5 h-5 text-gray-400" />
                
                <!-- User avatar -->
                <div class="w-12 h-12">
                    <img 
                        v-if="user?.avatar" 
                        :src="user.avatar" 
                        :alt="user.name"
                        class="w-full h-full rounded-full object-cover"
                    />
                    <div v-else class="w-full h-full rounded-full bg-ef-green-primary flex items-center justify-center">
                        <span class="text-white font-medium text-lg">
                            {{ user?.name?.charAt(0)?.toUpperCase() || '?' }}
                        </span>
                    </div>
                </div>
            </div>
            
            <h1 class="text-2xl font-bold text-text-primary mb-2">
                {{ client.name }} wants to access your account
            </h1>
            <p class="text-sm text-text-secondary">
                Signed in as {{ user.name }} ({{ user.email }})
            </p>
        </div>

        <!-- Error display -->
        <div v-if="form.errors.general" class="mb-6">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex">
                    <ExclamationTriangleIcon class="h-5 w-5 text-red-400 shrink-0" />
                    <div class="ml-3">
                        <p class="text-sm text-red-800">{{ form.errors.general }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permissions -->
        <div class="mb-8">
            <h3 class="text-lg font-medium text-text-primary mb-4">
                This will allow {{ client.name }} to:
            </h3>
            
            <div class="space-y-3">
                <div 
                    v-for="scope in scopesList" 
                    :key="scope.name"
                    class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg"
                >
                    <div class="shrink-0 mt-0.5">
                        <component 
                            :is="scope.icon" 
                            class="w-5 h-5 text-ef-green-primary"
                        />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center space-x-2">
                            <p class="text-sm font-medium text-text-primary">
                                {{ scope.title }}
                            </p>
                        </div>
                        <p class="text-xs text-text-secondary mt-1">
                            {{ scope.description }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Remember choice -->
        <div class="mb-6">
            <label class="flex items-center space-x-3 p-3 bg-blue-50 rounded-lg cursor-pointer">
                <input
                    v-model="form.remember"
                    type="checkbox"
                    class="rounded border-gray-300 text-ef-green-primary focus:ring-ef-green-primary"
                />
                <div class="flex-1">
                    <p class="text-sm font-medium text-text-primary">
                        Remember this choice
                    </p>
                    <p class="text-xs text-text-secondary">
                        Don't ask again for {{ client.name }} (you can change this later)
                    </p>
                </div>
            </label>
        </div>

        <!-- Action buttons -->
        <div class="space-y-3">
            <button
                @click="allow"
                :disabled="form.processing"
                class="w-full bg-ef-green-primary hover:bg-ef-green-dark disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-medium py-3 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-ef-green-primary focus:ring-offset-2"
            >
                <span v-if="form.processing">Processing...</span>
                <span v-else>Allow</span>
            </button>
            
            <button
                @click="deny"
                :disabled="form.processing"
                class="w-full bg-white hover:bg-gray-50 border border-gray-300 text-text-primary font-medium py-3 px-4 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Cancel
            </button>
        </div>

        <!-- Client-specific links -->
        <div v-if="client.policy_uri || client.tos_uri" class="mt-6 text-center">
            <div class="text-xs text-gray-500 space-x-4">
                <a 
                    v-if="client.policy_uri" 
                    :href="client.policy_uri" 
                    target="_blank"
                    class="hover:text-ef-green-primary"
                >
                    {{ client.name }} Privacy Policy
                </a>
                <span v-if="client.policy_uri && client.tos_uri">•</span>
                <a 
                    v-if="client.tos_uri" 
                    :href="client.tos_uri" 
                    target="_blank"
                    class="hover:text-ef-green-primary"
                >
                    {{ client.name }} Terms of Service
                </a>
            </div>
        </div>
    </AuthLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { 
    ExclamationTriangleIcon,
    ArrowRightIcon,
    UserIcon,
    UserCircleIcon,
    EnvelopeIcon,
    UsersIcon,
    ClockIcon,
    ShieldCheckIcon,
    CubeIcon as ApplicationIcon
} from '@heroicons/vue/24/outline'
import AuthLayout from '@Shared/layouts/AuthLayout.vue'

const props = defineProps({
    consent_challenge: String,
    client: Object,
    user: Object,
    scopes: Array,
    requestedAudience: Array
})

const form = useForm({
    consent_challenge: props.consent_challenge,
    action: 'allow',
    remember: false
})

// Scope configuration mapping
const SCOPE_CONFIG = {
    'openid': {
        title: 'Access your basic profile',
        description: 'View your basic profile information',
        icon: UserIcon
    },
    'profile': {
        title: 'Access your full profile',
        description: 'View your full profile information including name and avatar',
        icon: UserCircleIcon
    },
    'email': {
        title: 'Access your email',
        description: 'View your email address',
        icon: EnvelopeIcon
    },
    'groups': {
        title: 'Access your groups',
        description: 'View your group memberships',
        icon: UsersIcon
    },
    'offline_access': {
        title: 'Offline access',
        description: 'Access your data even when you are not logged in',
        icon: ClockIcon
    }
}

// Default scope configuration for unknown scopes
const DEFAULT_SCOPE_CONFIG = {
    icon: ShieldCheckIcon
}

// Computed property to map scopes to their configurations
const scopesList = computed(() => {
    return props.scopes.map(scope => {
        const scopeName = scope.name.toLowerCase().replace(' ', '_')
        const config = SCOPE_CONFIG[scopeName] || {
            ...DEFAULT_SCOPE_CONFIG,
            title: scope.name,
            description: scope.description
        }
        return {
            name: scopeName,
            title: config.title,
            description: config.description,
            icon: config.icon
        }
    })
})

const allow = () => {
    form.action = 'allow'
    form.post(route('auth.consent.submit'))
}

const deny = () => {
    form.action = 'deny'
    form.post(route('auth.consent.submit'))
}
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