<template>
    <Head :title="$t('apps_name')" />
    <AppDetailLayout :app="app" active-key="general">
        <div class="space-y-6">
            <!-- Not approved warning -->
            <div v-if="!app.approved" class="rounded-lg border border-yellow-300 bg-yellow-50 dark:border-yellow-600 dark:bg-yellow-900/30 p-4">
                <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">{{ $t('apps_not_approved_title') }}</p>
                <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">{{ $t('apps_not_approved_description') }}</p>
            </div>

            <!-- First-party indicator (read-only) -->
            <div v-if="app.first_party" class="rounded-lg border border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/30 p-3">
                <p class="text-sm font-medium text-blue-800 dark:text-blue-200">{{ $t('apps_first_party') }}</p>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
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
                    <Button type="submit" :disabled="form.processing">{{ $t('apps_save') }}</Button>
                </div>
            </form>
        </div>
    </AppDetailLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import AppDetailLayout from './Layout.vue'

const props = defineProps({
    app: Object,
})

const form = useForm({
    client_name: props.app.client_name,
    icon: null,
    description: props.app.description || '',
    app_url: props.app.app_url || '',
    developer_name: props.app.developer_name || '',
    privacy_policy_url: props.app.privacy_policy_url || '',
    terms_of_service_url: props.app.terms_of_service_url || '',
})

function submit() {
    form.post(route('developers.general.update', props.app.id), {
        _method: 'put',
        forceFormData: true,
    })
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
