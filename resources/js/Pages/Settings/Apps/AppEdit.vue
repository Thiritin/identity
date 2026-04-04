<template>
    <Head :title="$t('apps_edit')" />
    <div class="grid md:grid-cols-3 gap-6 md:gap-10">
        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('apps_edit') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('apps_edit_description') }}</p>
        </div>
        <div class="md:col-span-2 space-y-6">
            <div v-if="newSecret" class="rounded-lg border border-yellow-300 bg-yellow-50 dark:border-yellow-600 dark:bg-yellow-900/30 p-4">
                <p class="mb-2 text-sm font-medium text-yellow-800 dark:text-yellow-200">{{ $t('apps_secret_warning') }}</p>
                <div class="flex gap-2">
                    <Input :model-value="newSecret" readonly class="flex-1 font-mono text-sm" />
                    <Button variant="outline" size="sm" @click="copySecret">
                        {{ secretCopied ? $t('apps_secret_copied') : $t('copy') }}
                    </Button>
                </div>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_client_id') }}</label>
                    <Input :model-value="app.client_id" readonly class="bg-gray-50 dark:bg-primary-950 font-mono text-sm" />
                </div>

                <!-- First-party indicator (read-only) -->
                <div v-if="app.first_party" class="rounded-lg border border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/30 p-3">
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">{{ $t('apps_first_party') }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_name') }}</label>
                    <Input v-model="form.client_name" type="text" required class="bg-white dark:bg-primary-950" />
                    <p v-if="form.errors.client_name" class="text-xs text-destructive mt-1">{{ form.errors.client_name }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_icon') }}</label>
                    <div v-if="app.icon_url" class="mb-2">
                        <img :src="app.icon_url" alt="App icon" class="h-16 w-16 rounded-lg object-cover" />
                    </div>
                    <Input type="file" accept="image/*" @change="form.icon = $event.target.files[0]" class="bg-white dark:bg-primary-950" />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('apps_icon_hint') }}</p>
                    <p v-if="form.errors.icon" class="text-xs text-destructive mt-1">{{ form.errors.icon }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_description') }}</label>
                    <Input v-model="form.description" type="text" :required="!app.first_party" class="bg-white dark:bg-primary-950" />
                    <p v-if="form.errors.description" class="text-xs text-destructive mt-1">{{ form.errors.description }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_app_url') }}</label>
                    <Input v-model="form.app_url" type="url" placeholder="https://" :required="!app.first_party" class="bg-white dark:bg-primary-950" />
                    <p v-if="form.errors.app_url" class="text-xs text-destructive mt-1">{{ form.errors.app_url }}</p>
                </div>

                <!-- Third-party required fields -->
                <template v-if="!app.first_party">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_developer_name') }}</label>
                        <Input v-model="form.developer_name" type="text" required class="bg-white dark:bg-primary-950" />
                        <p v-if="form.errors.developer_name" class="text-xs text-destructive mt-1">{{ form.errors.developer_name }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_privacy_policy_url') }}</label>
                        <Input v-model="form.privacy_policy_url" type="url" placeholder="https://" required class="bg-white dark:bg-primary-950" />
                        <p v-if="form.errors.privacy_policy_url" class="text-xs text-destructive mt-1">{{ form.errors.privacy_policy_url }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_terms_of_service_url') }}</label>
                        <Input v-model="form.terms_of_service_url" type="url" placeholder="https://" required class="bg-white dark:bg-primary-950" />
                        <p v-if="form.errors.terms_of_service_url" class="text-xs text-destructive mt-1">{{ form.errors.terms_of_service_url }}</p>
                    </div>
                </template>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_redirect_uris') }}</label>
                    <div v-for="(uri, index) in form.redirect_uris" :key="'redirect-' + index" class="mb-2 flex gap-2">
                        <Input v-model="form.redirect_uris[index]" type="url" placeholder="https://" class="flex-1 bg-white dark:bg-primary-950" />
                        <Button v-if="form.redirect_uris.length > 1" variant="outline" size="sm" type="button" @click="form.redirect_uris.splice(index, 1)">×</Button>
                    </div>
                    <Button variant="outline" size="sm" type="button" @click="form.redirect_uris.push('')">{{ $t('apps_add_uri') }}</Button>
                    <p v-if="form.errors.redirect_uris" class="text-xs text-destructive mt-1">{{ form.errors.redirect_uris }}</p>
                    <template v-for="(error, key) in form.errors" :key="key">
                        <p v-if="key.startsWith('redirect_uris.')" class="text-xs text-destructive mt-1">{{ error }}</p>
                    </template>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_post_logout_redirect_uris') }}</label>
                    <div v-for="(uri, index) in form.post_logout_redirect_uris" :key="'logout-' + index" class="mb-2 flex gap-2">
                        <Input v-model="form.post_logout_redirect_uris[index]" type="url" placeholder="https://" class="flex-1 bg-white dark:bg-primary-950" />
                        <Button v-if="form.post_logout_redirect_uris.length > 1" variant="outline" size="sm" type="button" @click="form.post_logout_redirect_uris.splice(index, 1)">×</Button>
                    </div>
                    <Button variant="outline" size="sm" type="button" @click="form.post_logout_redirect_uris.push('')">{{ $t('apps_add_uri') }}</Button>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_frontchannel_logout_uri') }}</label>
                    <Input v-model="form.frontchannel_logout_uri" type="url" placeholder="https://" class="bg-white dark:bg-primary-950" />
                    <p v-if="form.errors.frontchannel_logout_uri" class="text-xs text-destructive mt-1">{{ form.errors.frontchannel_logout_uri }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_backchannel_logout_uri') }}</label>
                    <Input v-model="form.backchannel_logout_uri" type="url" placeholder="https://" class="bg-white dark:bg-primary-950" />
                    <p v-if="form.errors.backchannel_logout_uri" class="text-xs text-destructive mt-1">{{ form.errors.backchannel_logout_uri }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_scopes') }}</label>
                    <div class="space-y-2">
                        <div v-for="scope in availableScopes" :key="scope" class="flex items-center gap-2">
                            <Checkbox
                                :id="'scope-' + scope"
                                :model-value="form.scope.includes(scope)"
                                @update:model-value="toggleScope(scope)"
                            />
                            <label :for="'scope-' + scope" class="text-sm text-gray-700 dark:text-gray-300">{{ scope }}</label>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Button type="submit" :disabled="form.processing">{{ $t('apps_save') }}</Button>
                    <Button variant="destructive" type="button" @click="showRegenerateDialog = true">{{ $t('apps_regenerate_secret') }}</Button>
                    <Button variant="outline" as-child>
                        <Link :href="route('developers.index')">{{ $t('apps_back') }}</Link>
                    </Button>
                </div>
            </form>
        </div>
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

<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Checkbox } from '@/Components/ui/checkbox'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog'
import { ref } from 'vue'

const props = defineProps({
    app: Object,
    availableScopes: Array,
    clientSecret: {
        type: String,
        default: null,
    },
})

const newSecret = ref(props.clientSecret)
const showRegenerateDialog = ref(false)
const regenerating = ref(false)
const secretCopied = ref(false)

const form = useForm({
    client_name: props.app.client_name,
    icon: null,
    description: props.app.description || '',
    app_url: props.app.app_url || '',
    developer_name: props.app.developer_name || '',
    privacy_policy_url: props.app.privacy_policy_url || '',
    terms_of_service_url: props.app.terms_of_service_url || '',
    redirect_uris: props.app.redirect_uris.length ? [...props.app.redirect_uris] : [''],
    post_logout_redirect_uris: props.app.post_logout_redirect_uris.length ? [...props.app.post_logout_redirect_uris] : [''],
    frontchannel_logout_uri: props.app.frontchannel_logout_uri || '',
    backchannel_logout_uri: props.app.backchannel_logout_uri || '',
    scope: [...(props.app.scope || ['openid'])],
})

function toggleScope(scope) {
    const idx = form.scope.indexOf(scope)
    if (idx === -1) {
        form.scope.push(scope)
    } else {
        form.scope.splice(idx, 1)
    }
}

function submit() {
    form.redirect_uris = form.redirect_uris.filter(uri => uri.trim() !== '')
    form.post_logout_redirect_uris = form.post_logout_redirect_uris.filter(uri => uri.trim() !== '')
    form.post(route('developers.update', props.app.id), {
        _method: 'put',
        forceFormData: true,
    })
}

function regenerateSecret() {
    regenerating.value = true
    router.post(route('developers.regenerate-secret', props.app.id), {}, {
        preserveScroll: true,
        onSuccess: (page) => {
            newSecret.value = page.props?.clientSecret
            showRegenerateDialog.value = false
            regenerating.value = false
        },
        onError: () => {
            regenerating.value = false
        },
    })
}

async function copySecret() {
    if (newSecret.value) {
        await navigator.clipboard.writeText(newSecret.value)
        secretCopied.value = true
        setTimeout(() => (secretCopied.value = false), 2000)
    }
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
