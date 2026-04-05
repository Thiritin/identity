<template>
    <Head :title="$t('apps_delete')" />
    <AppDetailLayout :app="app" active-key="danger">
        <div class="space-y-6">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('apps_delete') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('apps_delete_confirm') }}</p>
            </div>
            <div>
                <Button variant="destructive" type="button" @click="showDeleteDialog = true">{{ $t('apps_delete') }}</Button>
            </div>
        </div>
    </AppDetailLayout>

    <Dialog v-model:open="showDeleteDialog">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ $t('apps_delete') }}</DialogTitle>
                <DialogDescription>{{ $t('apps_delete_confirm') }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="showDeleteDialog = false">{{ $t('cancel') }}</Button>
                <Button variant="destructive" @click="deleteApp">{{ $t('apps_delete') }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog'
import { ref } from 'vue'
import AppDetailLayout from './Layout.vue'

const props = defineProps({
    app: Object,
})

const showDeleteDialog = ref(false)

function deleteApp() {
    router.delete(route('developers.destroy', props.app.id))
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
