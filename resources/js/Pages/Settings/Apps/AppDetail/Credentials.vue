<template>
    <Head :title="$t('apps_client_id')" />
    <AppDetailLayout :app="app" active-key="credentials">
        <div class="space-y-6">
            <!-- One-time secret flash banner -->
            <div v-if="clientSecret" class="rounded-lg border border-yellow-300 bg-yellow-50 dark:border-yellow-600 dark:bg-yellow-900/30 p-4">
                <p class="mb-2 text-sm font-medium text-yellow-800 dark:text-yellow-200">{{ $t('apps_secret_warning') }}</p>
                <div class="flex gap-2">
                    <Input :model-value="clientSecret" readonly class="flex-1 font-mono text-sm" />
                    <Button variant="outline" size="sm" @click="copySecret">
                        {{ secretCopied ? $t('apps_secret_copied') : $t('copy') }}
                    </Button>
                </div>
            </div>

            <!-- Client ID -->
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_client_id') }}</label>
                <div class="flex gap-2">
                    <Input :model-value="app.client_id" readonly class="flex-1 font-mono text-sm bg-gray-50 dark:bg-primary-950" />
                    <Button variant="outline" size="sm" @click="copyClientId">
                        {{ clientIdCopied ? $t('apps_secret_copied') : $t('copy') }}
                    </Button>
                </div>
            </div>

            <!-- Regenerate secret -->
            <div>
                <Button variant="destructive" type="button" @click="showRegenerateDialog = true">{{ $t('apps_regenerate_secret') }}</Button>
            </div>
        </div>
    </AppDetailLayout>

    <Dialog v-model:open="showRegenerateDialog">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ $t('apps_regenerate_secret') }}</DialogTitle>
                <DialogDescription>{{ $t('apps_regenerate_confirm') }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="showRegenerateDialog = false">{{ $t('cancel') }}</Button>
                <Button variant="destructive" :disabled="regenerating" @click="regenerateSecret">{{ $t('apps_regenerate_secret') }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog'
import { ref } from 'vue'
import AppDetailLayout from './Layout.vue'

const props = defineProps({
    app: Object,
})

const page = usePage()
const clientSecret = ref(page.props.flash?.clientSecret)
const showRegenerateDialog = ref(false)
const regenerating = ref(false)
const secretCopied = ref(false)
const clientIdCopied = ref(false)

async function copySecret() {
    if (clientSecret.value) {
        await navigator.clipboard.writeText(clientSecret.value)
        secretCopied.value = true
        setTimeout(() => (secretCopied.value = false), 2000)
    }
}

async function copyClientId() {
    await navigator.clipboard.writeText(props.app.client_id)
    clientIdCopied.value = true
    setTimeout(() => (clientIdCopied.value = false), 2000)
}

function regenerateSecret() {
    regenerating.value = true
    router.post(route('developers.regenerate-secret', props.app.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            clientSecret.value = usePage().props.flash?.clientSecret
            showRegenerateDialog.value = false
            regenerating.value = false
        },
        onError: () => {
            regenerating.value = false
        },
    })
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
