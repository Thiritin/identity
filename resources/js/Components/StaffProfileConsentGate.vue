<template>
    <!-- Un-consented: "Enable your staff profile" button + auto-opening modal -->
    <div v-if="!consent.granted" data-testid="staff-profile-consent-gate">
        <div class="bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-6 sm:px-10 border-t border-gray-200/50 dark:border-primary-800/50">
            <div class="grid md:grid-cols-3 gap-6 md:gap-10">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('staff_profile_consent_state_heading') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('staff_profile_consent_notice_v1_intro') }}</p>
                </div>
                <div class="md:col-span-2 flex items-center">
                    <Button @click="showModal = true" data-testid="staff-profile-consent-open">
                        {{ $t('staff_profile_consent_reminder_expand') }}
                    </Button>
                </div>
            </div>
        </div>

        <!-- Consent notice modal -->
        <Dialog v-model:open="showModal">
            <DialogContent class="max-w-2xl max-h-[85vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{{ $t('staff_profile_consent_notice_v1_heading') }}</DialogTitle>
                </DialogHeader>

                <div class="space-y-4 py-2">
                    <p class="text-sm">{{ $t('staff_profile_consent_notice_v1_intro') }}</p>

                    <div v-for="category in categories" :key="category" class="border-l-2 border-gray-200 dark:border-gray-700 pl-3">
                        <h4 class="text-sm font-semibold">{{ $t(`staff_profile_consent_notice_v1_category_${category}_title`) }}</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $t(`staff_profile_consent_notice_v1_category_${category}_fields`) }}</p>
                        <p class="text-sm">{{ $t(`staff_profile_consent_notice_v1_category_${category}_purpose`) }}</p>
                    </div>

                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ $t('staff_profile_consent_notice_v1_recipients') }}</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ $t('staff_profile_consent_notice_v1_retention') }}</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ $t('staff_profile_consent_notice_v1_withdrawal') }}</p>
                </div>

                <DialogFooter>
                    <Button variant="outline" data-testid="staff-profile-consent-decline" @click="decline">
                        {{ $t('staff_profile_consent_decline') }}
                    </Button>
                    <Button data-testid="staff-profile-consent-accept" @click="$emit('grant')">
                        {{ $t('staff_profile_consent_accept') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>

    <!-- Consented: version-drift banner + default slot -->
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
import { ref, onMounted } from 'vue'
import { Button } from '@/Components/ui/button'
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog'

const SESSION_KEY = 'staff_profile_consent_dismissed'

const props = defineProps({
    consent: { type: Object, required: true },
})

defineEmits(['grant'])

const showModal = ref(false)

onMounted(() => {
    if (!props.consent.granted && !sessionStorage.getItem(SESSION_KEY)) {
        showModal.value = true
    }
})

function decline() {
    sessionStorage.setItem(SESSION_KEY, '1')
    showModal.value = false
}

const categories = ['identity', 'address', 'emergency_contact', 'skills', 'credits', 'history']
</script>
