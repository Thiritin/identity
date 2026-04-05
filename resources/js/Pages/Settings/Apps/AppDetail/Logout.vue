<template>
    <Head :title="$t('apps_frontchannel_logout_uri')" />
    <AppDetailLayout :app="app" active-key="logout">
        <form class="space-y-6" @submit.prevent="submit">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_post_logout_redirect_uris') }}</label>
                <div v-for="(uri, index) in form.post_logout_redirect_uris" :key="'logout-' + index" class="mb-2 flex gap-2">
                    <Input v-model="form.post_logout_redirect_uris[index]" type="url" placeholder="https://" class="flex-1 bg-white dark:bg-primary-950" />
                    <Button v-if="form.post_logout_redirect_uris.length > 1" variant="outline" size="sm" type="button" @click="form.post_logout_redirect_uris.splice(index, 1)">×</Button>
                </div>
                <Button variant="outline" size="sm" type="button" @click="form.post_logout_redirect_uris.push('')">{{ $t('apps_add_uri') }}</Button>
                <p v-if="form.errors.post_logout_redirect_uris" class="text-xs text-destructive mt-1">{{ form.errors.post_logout_redirect_uris }}</p>
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
                <Button type="submit" :disabled="form.processing">{{ $t('apps_save') }}</Button>
            </div>
        </form>
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
    post_logout_redirect_uris: props.app.post_logout_redirect_uris.length ? [...props.app.post_logout_redirect_uris] : [''],
    frontchannel_logout_uri: props.app.frontchannel_logout_uri || '',
    backchannel_logout_uri: props.app.backchannel_logout_uri || '',
})

function submit() {
    form.post_logout_redirect_uris = form.post_logout_redirect_uris.filter(uri => uri.trim() !== '')
    form.put(route('developers.logout.update', props.app.id))
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
