<template>
    <Head :title="app.client_name" />
    <div class="grid md:grid-cols-3 gap-6 md:gap-10">
        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ app.client_name }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('apps_show_description') }}</p>
        </div>
        <div class="md:col-span-2 space-y-6">
            <div v-if="!app.approved" class="rounded-lg border border-yellow-300 bg-yellow-50 dark:border-yellow-600 dark:bg-yellow-900/30 p-4">
                <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">{{ $t('apps_not_approved_title') }}</p>
                <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">{{ $t('apps_not_approved_description') }}</p>
            </div>

            <div v-if="clientSecret" class="rounded-lg border border-yellow-300 bg-yellow-50 dark:border-yellow-600 dark:bg-yellow-900/30 p-4">
                <p class="mb-2 text-sm font-medium text-yellow-800 dark:text-yellow-200">{{ $t('apps_secret_warning') }}</p>
                <div class="flex gap-2">
                    <Input :model-value="clientSecret" readonly class="flex-1 font-mono text-sm" />
                    <Button variant="outline" size="sm" @click="copySecret">
                        {{ secretCopied ? $t('apps_secret_copied') : $t('copy') }}
                    </Button>
                </div>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_client_id') }}</label>
                <div class="flex gap-2">
                    <Input :model-value="app.client_id" readonly class="flex-1 font-mono text-sm" />
                    <Button variant="outline" size="sm" @click="copyClientId">
                        {{ clientIdCopied ? $t('apps_secret_copied') : $t('copy') }}
                    </Button>
                </div>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_redirect_uris') }}</label>
                <div class="space-y-1">
                    <div v-for="uri in app.redirect_uris" :key="uri" class="font-mono text-sm text-gray-600 dark:text-gray-400">{{ uri }}</div>
                </div>
            </div>

            <div v-if="app.post_logout_redirect_uris?.length">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_post_logout_redirect_uris') }}</label>
                <div class="space-y-1">
                    <div v-for="uri in app.post_logout_redirect_uris" :key="uri" class="font-mono text-sm text-gray-600 dark:text-gray-400">{{ uri }}</div>
                </div>
            </div>

            <div v-if="app.scope?.length">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_scopes') }}</label>
                <div class="flex flex-wrap gap-2">
                    <Badge v-for="scope in app.scope" :key="scope" variant="secondary">{{ scope }}</Badge>
                </div>
            </div>

            <div class="flex gap-2">
                <Button as-child>
                    <Link :href="route('developers.edit', app.id)">{{ $t('apps_edit') }}</Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="route('developers.index')">{{ $t('apps_back') }}</Link>
                </Button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Badge } from '@/Components/ui/badge'
import { ref } from 'vue'

const props = defineProps({
    app: Object,
    clientSecret: {
        type: String,
        default: null,
    },
})

const secretCopied = ref(false)
const clientIdCopied = ref(false)

async function copySecret() {
    if (props.clientSecret) {
        await navigator.clipboard.writeText(props.clientSecret)
        secretCopied.value = true
        setTimeout(() => (secretCopied.value = false), 2000)
    }
}

async function copyClientId() {
    await navigator.clipboard.writeText(props.app.client_id)
    clientIdCopied.value = true
    setTimeout(() => (clientIdCopied.value = false), 2000)
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
