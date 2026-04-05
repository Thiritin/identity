<template>
    <Head :title="$t('apps_create')" />
    <div class="grid md:grid-cols-3 gap-6 md:gap-10">
        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('apps_create') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('apps_create_description') }}</p>
        </div>
        <div class="md:col-span-2">
            <form class="space-y-6" @submit.prevent="submit">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_name') }}</label>
                    <Input v-model="form.client_name" type="text" required class="bg-white dark:bg-primary-950" />
                    <p v-if="form.errors.client_name" class="text-xs text-destructive mt-1">{{ form.errors.client_name }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_redirect_uri') }}</label>
                    <Input v-model="form.redirect_uri" type="url" placeholder="https://" required class="bg-white dark:bg-primary-950" />
                    <p v-if="form.errors.redirect_uri" class="text-xs text-destructive mt-1">{{ form.errors.redirect_uri }}</p>
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
})

const form = useForm({
    client_name: '',
    redirect_uri: '',
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
    form.post(route('developers.store'))
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
