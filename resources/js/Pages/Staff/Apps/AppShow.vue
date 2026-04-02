<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SiteHeader from '@/Components/Staff/SiteHeader.vue';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    app: Object,
    clientSecret: {
        type: String,
        default: null,
    },
});

const secretCopied = ref(false);

async function copySecret() {
    if (props.clientSecret) {
        await navigator.clipboard.writeText(props.clientSecret);
        secretCopied.value = true;
        setTimeout(() => (secretCopied.value = false), 2000);
    }
}

const clientIdCopied = ref(false);

async function copyClientId() {
    await navigator.clipboard.writeText(props.app.client_id);
    clientIdCopied.value = true;
    setTimeout(() => (clientIdCopied.value = false), 2000);
}
</script>

<template>
    <SiteHeader :title="app.client_name">
        <template #action>
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="route('staff.apps.edit', app.id)">{{ $t('apps_edit') }}</Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="route('staff.apps.index')">{{ $t('apps_back') }}</Link>
                </Button>
            </div>
        </template>
    </SiteHeader>

    <div class="max-w-2xl space-y-6 px-6 py-4">
        <div v-if="clientSecret" class="rounded-lg border border-yellow-300 bg-yellow-50 p-4">
            <p class="mb-2 text-sm font-medium text-yellow-800">{{ $t('apps_secret_warning') }}</p>
            <div class="flex gap-2">
                <Input :model-value="clientSecret" readonly class="flex-1 font-mono text-sm" />
                <Button variant="outline" size="sm" @click="copySecret">
                    {{ secretCopied ? $t('apps_secret_copied') : 'Copy' }}
                </Button>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">{{ $t('apps_client_id') }}</label>
            <div class="flex gap-2">
                <Input :model-value="app.client_id" readonly class="flex-1 font-mono text-sm" />
                <Button variant="outline" size="sm" @click="copyClientId">
                    {{ clientIdCopied ? $t('apps_secret_copied') : 'Copy' }}
                </Button>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">{{ $t('apps_redirect_uris') }}</label>
            <div class="space-y-1">
                <div v-for="uri in app.redirect_uris" :key="uri" class="font-mono text-sm text-gray-600">{{ uri }}</div>
            </div>
        </div>

        <div v-if="app.post_logout_redirect_uris?.length">
            <label class="mb-1 block text-sm font-medium text-gray-700">{{ $t('apps_post_logout_redirect_uris') }}</label>
            <div class="space-y-1">
                <div v-for="uri in app.post_logout_redirect_uris" :key="uri" class="font-mono text-sm text-gray-600">{{ uri }}</div>
            </div>
        </div>

        <div v-if="app.scope?.length">
            <label class="mb-1 block text-sm font-medium text-gray-700">{{ $t('apps_scopes') }}</label>
            <div class="flex flex-wrap gap-2">
                <Badge v-for="scope in app.scope" :key="scope" variant="secondary">{{ scope }}</Badge>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">{{ $t('apps_created_at') }}</label>
            <p class="text-sm text-gray-600">{{ app.created_at }}</p>
        </div>
    </div>
</template>
