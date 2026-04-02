<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SiteHeader from '@/Components/Staff/SiteHeader.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Checkbox } from '@/Components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

defineOptions({ layout: AppLayout });

const props = defineProps({
    app: Object,
    availableScopes: Array,
    clientSecret: {
        type: String,
        default: null,
    },
});

const newSecret = ref(props.clientSecret);

const form = useForm({
    client_name: props.app.client_name,
    redirect_uris: props.app.redirect_uris.length ? [...props.app.redirect_uris] : [''],
    post_logout_redirect_uris: props.app.post_logout_redirect_uris.length ? [...props.app.post_logout_redirect_uris] : [''],
    scope: [...(props.app.scope || ['openid'])],
});

function addRedirectUri() {
    form.redirect_uris.push('');
}

function removeRedirectUri(index) {
    form.redirect_uris.splice(index, 1);
}

function addPostLogoutUri() {
    form.post_logout_redirect_uris.push('');
}

function removePostLogoutUri(index) {
    form.post_logout_redirect_uris.splice(index, 1);
}

function toggleScope(scope) {
    const idx = form.scope.indexOf(scope);
    if (idx === -1) {
        form.scope.push(scope);
    } else {
        form.scope.splice(idx, 1);
    }
}

function submit() {
    form.redirect_uris = form.redirect_uris.filter(uri => uri.trim() !== '');
    form.post_logout_redirect_uris = form.post_logout_redirect_uris.filter(uri => uri.trim() !== '');
    form.put(route('staff.apps.update', props.app.id), {
        onSuccess: () => {
            const msg = usePage().props.flash?.message;
            if (msg) {
                toast.success(msg);
            }
        },
    });
}

const showRegenerateDialog = ref(false);
const regenerating = ref(false);
const secretCopied = ref(false);

function regenerateSecret() {
    regenerating.value = true;
    router.post(route('staff.apps.regenerate-secret', props.app.id), {}, {
        preserveScroll: true,
        onSuccess: (page) => {
            newSecret.value = page.props?.clientSecret;
            showRegenerateDialog.value = false;
            regenerating.value = false;
        },
        onError: () => {
            regenerating.value = false;
        },
    });
}

async function copySecret() {
    if (newSecret.value) {
        await navigator.clipboard.writeText(newSecret.value);
        secretCopied.value = true;
        setTimeout(() => (secretCopied.value = false), 2000);
    }
}
</script>

<template>
    <SiteHeader :title="$t('apps_edit')">
        <template #action>
            <Button variant="outline" as-child>
                <Link :href="route('staff.apps.index')">{{ $t('apps_back') }}</Link>
            </Button>
        </template>
    </SiteHeader>

    <div class="max-w-2xl px-6 py-4">
        <div v-if="newSecret" class="mb-6 rounded-lg border border-yellow-300 bg-yellow-50 p-4">
            <p class="mb-2 text-sm font-medium text-yellow-800">{{ $t('apps_secret_warning') }}</p>
            <div class="flex gap-2">
                <Input :model-value="newSecret" readonly class="flex-1 font-mono text-sm" />
                <Button variant="outline" size="sm" @click="copySecret">
                    {{ secretCopied ? $t('apps_secret_copied') : 'Copy' }}
                </Button>
            </div>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ $t('apps_client_id') }}</label>
                <Input :model-value="app.client_id" readonly class="bg-gray-50 font-mono text-sm" />
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ $t('apps_name') }}</label>
                <Input v-model="form.client_name" type="text" required />
                <p v-if="form.errors.client_name" class="mt-1 text-sm text-red-600">{{ form.errors.client_name }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ $t('apps_redirect_uris') }}</label>
                <div v-for="(uri, index) in form.redirect_uris" :key="'redirect-' + index" class="mb-2 flex gap-2">
                    <Input v-model="form.redirect_uris[index]" type="url" placeholder="https://" class="flex-1" />
                    <Button v-if="form.redirect_uris.length > 1" variant="outline" size="sm" type="button" @click="removeRedirectUri(index)">×</Button>
                </div>
                <Button variant="outline" size="sm" type="button" @click="addRedirectUri">{{ $t('apps_add_uri') }}</Button>
                <p v-if="form.errors.redirect_uris" class="mt-1 text-sm text-red-600">{{ form.errors.redirect_uris }}</p>
                <template v-for="(error, key) in form.errors" :key="key">
                    <p v-if="key.startsWith('redirect_uris.')" class="mt-1 text-sm text-red-600">{{ error }}</p>
                </template>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ $t('apps_post_logout_redirect_uris') }}</label>
                <div v-for="(uri, index) in form.post_logout_redirect_uris" :key="'logout-' + index" class="mb-2 flex gap-2">
                    <Input v-model="form.post_logout_redirect_uris[index]" type="url" placeholder="https://" class="flex-1" />
                    <Button v-if="form.post_logout_redirect_uris.length > 1" variant="outline" size="sm" type="button" @click="removePostLogoutUri(index)">×</Button>
                </div>
                <Button variant="outline" size="sm" type="button" @click="addPostLogoutUri">{{ $t('apps_add_uri') }}</Button>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ $t('apps_scopes') }}</label>
                <div class="space-y-2">
                    <div v-for="scope in availableScopes" :key="scope" class="flex items-center gap-2">
                        <Checkbox
                            :id="'scope-' + scope"
                            :model-value="form.scope.includes(scope)"
                            @update:model-value="toggleScope(scope)"
                        />
                        <label :for="'scope-' + scope" class="text-sm">{{ scope }}</label>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button type="submit" :disabled="form.processing">{{ $t('apps_save') }}</Button>
                <Button variant="destructive" type="button" @click="showRegenerateDialog = true">{{ $t('apps_regenerate_secret') }}</Button>
            </div>
        </form>
    </div>

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
