<template>
    <div v-if="!consent.granted && !collapsed" class="relative" data-testid="staff-profile-consent-gate">
        <div class="pointer-events-none select-none blur-md opacity-40" aria-hidden="true">
            <slot name="placeholder" />
        </div>
        <div class="absolute inset-0 flex items-start justify-center p-6 overflow-y-auto">
            <div class="bg-white dark:bg-primary-900 rounded-xl shadow-xl max-w-2xl w-full p-6 my-6 space-y-4">
                <h3 class="text-lg font-semibold">{{ $t('staff_profile_consent_notice_v1_heading') }}</h3>
                <p class="text-sm">{{ $t('staff_profile_consent_notice_v1_intro') }}</p>

                <div v-for="category in categories" :key="category" class="border-l-2 border-gray-200 dark:border-gray-700 pl-3">
                    <h4 class="text-sm font-semibold">{{ $t(`staff_profile_consent_notice_v1_category_${category}_title`) }}</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ $t(`staff_profile_consent_notice_v1_category_${category}_fields`) }}</p>
                    <p class="text-sm">{{ $t(`staff_profile_consent_notice_v1_category_${category}_purpose`) }}</p>
                </div>

                <p class="text-xs text-gray-600 dark:text-gray-400">{{ $t('staff_profile_consent_notice_v1_recipients') }}</p>
                <p class="text-xs text-gray-600 dark:text-gray-400">{{ $t('staff_profile_consent_notice_v1_retention') }}</p>
                <p class="text-xs text-gray-600 dark:text-gray-400">{{ $t('staff_profile_consent_notice_v1_withdrawal') }}</p>

                <div class="flex gap-2 justify-end pt-2">
                    <Button variant="outline" data-testid="staff-profile-consent-decline" @click="collapsed = true">
                        {{ $t('staff_profile_consent_decline') }}
                    </Button>
                    <Button data-testid="staff-profile-consent-accept" @click="$emit('grant')">
                        {{ $t('staff_profile_consent_accept') }}
                    </Button>
                </div>
            </div>
        </div>
    </div>

    <div v-else-if="!consent.granted && collapsed" class="bg-gray-50 dark:bg-primary-900/50 border border-dashed rounded-lg px-4 py-3 flex items-center justify-between">
        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $t('staff_profile_consent_reminder_collapsed') }}</span>
        <button type="button" class="text-sm underline" @click="collapsed = false">
            {{ $t('staff_profile_consent_reminder_expand') }}
        </button>
    </div>

    <div v-else>
        <div v-if="!consent.is_current" class="bg-amber-50 dark:bg-amber-900/30 border-l-4 border-amber-400 px-4 py-3 mb-4">
            <span class="text-sm">{{ $t('staff_profile_consent_notice_updated') }}</span>
            <button type="button" class="text-sm underline ml-2" @click="$emit('grant')">
                {{ $t('staff_profile_consent_notice_review') }}
            </button>
        </div>
        <slot />
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Button } from '@/Components/ui/button'

defineProps({
    consent: { type: Object, required: true },
})

defineEmits(['grant'])

const collapsed = ref(false)

const categories = ['identity', 'address', 'emergency_contact', 'skills', 'credits', 'history']
</script>
