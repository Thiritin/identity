<template>
    <!-- Un-consented: "Enable your staff profile" button + auto-opening modal -->
    <div v-if="!consent.granted" data-testid="staff-profile-consent-gate">
        <div class="bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-6 sm:px-10 border-t border-gray-200/50 dark:border-primary-800/50">
            <div class="grid md:grid-cols-3 gap-6 md:gap-10">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('staff_profile_consent_state_heading') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('staff_profile_consent_state_not_granted') }}</p>
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

                    <div v-for="cat in categories" :key="cat.key" class="border-l-2 border-gray-200 dark:border-gray-700 pl-3 space-y-1">
                        <h4 class="text-sm font-semibold">{{ $t(`staff_profile_consent_notice_v1_category_${cat.key}_title`) }}</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $t(`staff_profile_consent_notice_v1_category_${cat.key}_fields`) }}</p>
                        <p class="text-sm">{{ $t(`staff_profile_consent_notice_v1_category_${cat.key}_purpose`) }}</p>

                        <!-- Visibility selector for categories that support it -->
                        <div v-if="cat.visibilityKeys" class="flex items-center gap-2 pt-1">
                            <label class="text-xs text-gray-500 dark:text-gray-400 shrink-0">{{ $t('staff_profile_visibility') }}:</label>
                            <Select :model-value="visibility[cat.key]" @update:model-value="v => visibility[cat.key] = v">
                                <SelectTrigger class="h-7 text-xs w-44">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="opt in visibilityOptions" :key="opt.value" :value="opt.value">
                                        {{ $t(opt.label) }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ $t('staff_profile_consent_notice_v1_recipients') }}</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ $t('staff_profile_consent_notice_v1_retention') }}</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ $t('staff_profile_consent_notice_v1_withdrawal') }}</p>
                </div>

                <DialogFooter>
                    <Button variant="outline" data-testid="staff-profile-consent-decline" @click="decline">
                        {{ $t('staff_profile_consent_decline') }}
                    </Button>
                    <Button data-testid="staff-profile-consent-accept" @click="grant">
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
            <button type="button" class="text-sm underline ml-2" @click="grant">
                {{ $t('staff_profile_consent_notice_review') }}
            </button>
        </div>
        <slot />
    </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { Button } from '@/Components/ui/button'
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select'

const SESSION_KEY = 'staff_profile_consent_dismissed'

const props = defineProps({
    consent: { type: Object, required: true },
})

const emit = defineEmits(['grant'])

const showModal = ref(false)

// Categories with optional visibility key mappings.
// visibilityKeys: the staff_profile_visibility JSON keys this category controls.
const categories = [
    { key: 'identity', visibilityKeys: ['firstname', 'lastname', 'pronouns', 'birthdate', 'phone'] },
    { key: 'address', visibilityKeys: ['address'] },
    { key: 'emergency_contact', visibilityKeys: ['emergency_contact'] },
    { key: 'skills', visibilityKeys: null },
    { key: 'credits', visibilityKeys: null },
    { key: 'history', visibilityKeys: null },
]

const visibilityOptions = [
    { value: 'all_staff', label: 'staff_profile_visibility_all_staff' },
    { value: 'my_departments', label: 'staff_profile_visibility_my_departments' },
    { value: 'leads_and_directors', label: 'staff_profile_visibility_leads_and_directors' },
    { value: 'directors_only', label: 'staff_profile_visibility_directors_only' },
]

// Default visibility per category (matches User::STAFF_FIELD_DEFAULT_VISIBILITY)
const visibility = reactive({
    identity: 'all_staff',
    address: 'directors_only',
    emergency_contact: 'all_staff',
})

onMounted(() => {
    if (!props.consent.granted && !sessionStorage.getItem(SESSION_KEY)) {
        showModal.value = true
    }
})

function decline() {
    sessionStorage.setItem(SESSION_KEY, '1')
    showModal.value = false
}

function grant() {
    // Expand category-level choices into per-field visibility keys
    const perField = {}
    for (const cat of categories) {
        if (cat.visibilityKeys) {
            for (const key of cat.visibilityKeys) {
                perField[key] = visibility[cat.key]
            }
        }
    }
    emit('grant', perField)
}
</script>
