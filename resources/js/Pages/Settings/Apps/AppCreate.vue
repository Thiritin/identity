<template>
    <Head :title="$t('apps_create')" />
    <div class="grid md:grid-cols-3 gap-6 md:gap-10">
        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('apps_create') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('apps_create_description') }}</p>
        </div>
        <div class="md:col-span-2">
            <form class="space-y-6" @submit.prevent="submit">
                <!-- First Party toggle (staff only) -->
                <div v-if="isStaff" class="flex items-center gap-2">
                    <Checkbox id="first_party" v-model="form.first_party" />
                    <label for="first_party" class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('apps_first_party') }}</label>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_name') }}</label>
                    <Input v-model="form.client_name" type="text" required class="bg-white dark:bg-primary-950" />
                    <p v-if="form.errors.client_name" class="text-xs text-destructive mt-1">{{ form.errors.client_name }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_description') }}</label>
                    <Input v-model="form.description" type="text" :required="!form.first_party" class="bg-white dark:bg-primary-950" />
                    <p v-if="form.errors.description" class="text-xs text-destructive mt-1">{{ form.errors.description }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_app_url') }}</label>
                    <Input v-model="form.app_url" type="url" placeholder="https://" :required="!form.first_party" class="bg-white dark:bg-primary-950" />
                    <p v-if="form.errors.app_url" class="text-xs text-destructive mt-1">{{ form.errors.app_url }}</p>
                </div>

                <!-- Third-party required fields -->
                <template v-if="!form.first_party">
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

                <div class="flex gap-2">
                    <Button type="submit" :disabled="form.processing">{{ $t('apps_create') }}</Button>
                    <Button variant="outline" as-child>
                        <Link :href="route('developers.index')">{{ $t('cancel') }}</Link>
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Checkbox } from '@/Components/ui/checkbox'

const props = defineProps({
    availableScopes: Array,
    isStaff: Boolean,
})

const form = useForm({
    client_name: '',
    first_party: props.isStaff,
    description: '',
    app_url: '',
    developer_name: '',
    privacy_policy_url: '',
    terms_of_service_url: '',
    redirect_uris: [''],
    post_logout_redirect_uris: [''],
    frontchannel_logout_uri: '',
    backchannel_logout_uri: '',
    scope: ['openid'],
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
    form.post(route('developers.store'))
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
