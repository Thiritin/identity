<template>
    <Head :title="$t('apps_redirect_uris')" />
    <AppDetailLayout :app="app" active-key="oauth">
        <form class="space-y-6" @submit.prevent="submit">
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

            <div>
                <Button type="submit" :disabled="form.processing">{{ $t('apps_save') }}</Button>
            </div>
        </form>
    </AppDetailLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Checkbox } from '@/Components/ui/checkbox'
import AppDetailLayout from './Layout.vue'

const props = defineProps({
    app: Object,
    availableScopes: Array,
})

const form = useForm({
    redirect_uris: props.app.redirect_uris.length ? [...props.app.redirect_uris] : [''],
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
    form.put(route('developers.oauth.update', props.app.id))
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
