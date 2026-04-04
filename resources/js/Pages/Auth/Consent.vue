<template>
    <Head :title="app.name" />
    <div class="text-center mb-6">
        <div class="flex justify-center mb-4">
            <img
                v-if="app.logoUri"
                :src="app.logoUri"
                :alt="app.name"
                class="size-16 rounded-lg object-contain"
            />
            <div
                v-else
                class="size-16 rounded-lg bg-primary-100 dark:bg-primary-800 flex items-center justify-center"
            >
                <Globe class="size-8 text-primary-500" />
            </div>
        </div>
        <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ app.name }}
            <span class="font-normal text-gray-600 dark:text-primary-300">{{ $t('consent_title') }}</span>
        </h1>
        <p v-if="app.developerName" class="text-sm text-gray-500 dark:text-primary-400">
            {{ $t('consent_developed_by', { developer: app.developerName }) }}
        </p>
        <p v-if="app.description" class="mt-2 text-sm text-gray-600 dark:text-primary-300">{{ app.description }}</p>
    </div>

    <div class="space-y-4">
        <p class="text-sm text-gray-700 dark:text-primary-300">
            {{ $t('consent_data_shared', { app: app.name }) }}
        </p>

        <ul class="space-y-3" role="list">
            <li
                v-for="scope in scopes"
                :key="scope"
                class="flex items-center gap-3 text-sm text-gray-700 dark:text-primary-300"
            >
                <component :is="scopeIcons[scope]" class="size-4 shrink-0 text-primary-500" />
                <span>{{ $t(scopeLabels[scope] || scope) }}</span>
            </li>
        </ul>

        <div
            v-if="app.privacyPolicyUrl || app.termsOfServiceUrl"
            class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-primary-400"
        >
            <a
                v-if="app.privacyPolicyUrl"
                :href="app.privacyPolicyUrl"
                target="_blank"
                rel="noopener"
                class="hover:text-gray-700 dark:hover:text-primary-300 underline"
            >{{ $t('consent_privacy_policy') }}</a>
            <a
                v-if="app.termsOfServiceUrl"
                :href="app.termsOfServiceUrl"
                target="_blank"
                rel="noopener"
                class="hover:text-gray-700 dark:hover:text-primary-300 underline"
            >{{ $t('consent_terms_of_service') }}</a>
        </div>

        <div class="flex flex-col gap-3 pt-2">
            <Button :disabled="processing" class="w-full" @click="allowAccess">
                {{ $t('consent_allow') }}
                <Check class="size-4" />
            </Button>
            <Button :disabled="processing" variant="outline" class="w-full" @click="denyAccess">
                {{ $t('consent_deny') }}
            </Button>
        </div>
    </div>
</template>
<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { Check, Globe, Mail, User, Users } from 'lucide-vue-next'
import { ref } from 'vue'

defineOptions({ layout: AuthLayout })

const props = defineProps({
    consentChallenge: String,
    app: Object,
    scopes: Array,
})

const processing = ref(false)

const scopeLabels = {
    email: 'consent_scope_email',
    profile: 'consent_scope_profile',
    groups: 'consent_scope_groups',
}

const scopeIcons = {
    email: Mail,
    profile: User,
    groups: Users,
}

function allowAccess() {
    processing.value = true
    router.post(route('auth.consent.accept'), {
        consent_challenge: props.consentChallenge,
    })
}

function denyAccess() {
    processing.value = true
    router.post(route('auth.consent.deny'), {
        consent_challenge: props.consentChallenge,
    })
}
</script>
