<template>
    <Head :title="$t('backup_codes_title')" />
    <div>
        <Link :href="route('settings.security')" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-4">
            <ArrowLeft class="h-4 w-4" />
            {{ $t('security') }}
        </Link>

        <SettingsHeader>{{ $t('backup_codes_title') }}</SettingsHeader>
        <SettingsSubHeader class="mb-4">{{ $t('backup_codes_subtitle') }}</SettingsSubHeader>

        <!-- Freshly generated codes display -->
        <div v-if="backupCodes">
            <div class="rounded-lg border border-yellow-300 bg-yellow-50 dark:border-yellow-700 dark:bg-yellow-900/20 p-4 mb-4">
                <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">{{ $t('backup_codes_save_warning') }}</p>
            </div>
            <div class="grid grid-cols-2 gap-2 mb-4">
                <div v-for="code in formattedCodes" :key="code"
                     class="rounded bg-gray-100 dark:bg-gray-800 px-3 py-2 text-center font-mono text-sm tracking-wider">
                    {{ code }}
                </div>
            </div>
            <div class="flex gap-3">
                <Button variant="outline" size="sm" @click="copyAllCodes">
                    <Copy class="h-4 w-4 mr-1" />
                    {{ copied ? $t('backup_codes_copied') : $t('backup_codes_copy_all') }}
                </Button>
                <Button size="sm" as-child>
                    <Link :href="route('settings.security')">{{ $t('backup_codes_done') }}</Link>
                </Button>
            </div>
        </div>

        <!-- Normal view (no freshly generated codes) -->
        <div v-else>
            <template v-if="hasBackupCodes">
                <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ $t('security_backup_codes_remaining', { count: remainingCount }) }}
                </p>
                <div v-if="remainingCount <= 2 && remainingCount > 0"
                     class="rounded-lg border border-yellow-300 bg-yellow-50 dark:border-yellow-700 dark:bg-yellow-900/20 p-3 mb-4">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">{{ $t('backup_codes_low_warning') }}</p>
                </div>
                <div v-if="remainingCount === 0"
                     class="rounded-lg border border-destructive/50 bg-destructive/10 p-3 mb-4">
                    <p class="text-sm text-destructive">{{ $t('backup_codes_none_warning') }}</p>
                </div>
            </template>

            <!-- Regenerate form -->
            <div v-if="!showRegenerateForm">
                <Button variant="outline" @click="showRegenerateForm = true">{{ $t('backup_codes_regenerate') }}</Button>
            </div>
            <div v-else class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ $t('backup_codes_regenerate_warning') }}</p>
                <form @submit.prevent="submitRegenerate" class="space-y-4">
                    <div class="flex flex-col gap-2">
                        <label for="regenerate_password">{{ $t('password') }}</label>
                        <Input id="regenerate_password" type="password"
                               v-model="regenerateForm.password"
                               :class="{ 'border-destructive': regenerateForm.errors.password }" />
                        <p v-if="regenerateForm.errors.password" class="text-sm text-destructive">
                            {{ regenerateForm.errors.password }}
                        </p>
                        <p v-if="$page.props.errors.throttle" class="text-sm text-destructive">
                            {{ $page.props.errors.throttle }}
                        </p>
                    </div>
                    <div class="flex justify-end gap-3">
                        <Button type="submit" size="sm" :disabled="regenerateForm.processing" class="order-2">
                            {{ $t('backup_codes_regenerate') }}
                        </Button>
                        <Button type="button" variant="secondary" size="sm" @click="showRegenerateForm = false" class="order-1">{{ $t('cancel') }}</Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Copy } from 'lucide-vue-next'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import SettingsHeader from '@/Components/Settings/SettingsHeader.vue'
import SettingsSubHeader from '@/Components/Settings/SettingsSubHeader.vue'

const props = defineProps({
    remainingCount: Number,
    hasBackupCodes: Boolean,
    backupCodes: Array,
})

const showRegenerateForm = ref(false)
const copied = ref(false)

const formattedCodes = computed(() => {
    if (!props.backupCodes) return []
    return props.backupCodes.map(code => code.substring(0, 4) + '-' + code.substring(4))
})

const regenerateForm = useForm('post', route('settings.two-factor.backup-codes.regenerate'), {
    password: '',
})

function submitRegenerate() {
    regenerateForm.submit({
        preserveScroll: true,
        onSuccess: () => {
            showRegenerateForm.value = false
            regenerateForm.reset()
        },
    })
}

async function copyAllCodes() {
    if (!props.backupCodes) return
    const text = formattedCodes.value.join('\n')
    await navigator.clipboard.writeText(text)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
