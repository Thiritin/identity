<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SiteHeader from '@/Components/Staff/SiteHeader.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Checkbox } from '@/Components/ui/checkbox';
import { Link, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });

const props = defineProps({
    availableScopes: Array,
});

const form = useForm({
    client_name: '',
    redirect_uris: [''],
    post_logout_redirect_uris: [''],
    scope: ['openid'],
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
    form.post(route('staff.apps.store'));
}
</script>

<template>
    <SiteHeader :title="$t('apps_create')">
        <template #action>
            <Button variant="outline" as-child>
                <Link :href="route('staff.apps.index')">{{ $t('apps_back') }}</Link>
            </Button>
        </template>
    </SiteHeader>

    <div class="max-w-2xl px-6 py-4">
        <form class="space-y-6" @submit.prevent="submit">
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

            <Button type="submit" :disabled="form.processing">{{ $t('apps_create') }}</Button>
        </form>
    </div>
</template>
