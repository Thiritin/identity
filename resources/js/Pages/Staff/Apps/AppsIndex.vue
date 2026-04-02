<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SiteHeader from '@/Components/Staff/SiteHeader.vue';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    apps: Array,
});

const deleteTarget = ref(null);
const showDeleteDialog = ref(false);

function confirmDelete(app) {
    deleteTarget.value = app;
    showDeleteDialog.value = true;
}

function executeDelete() {
    if (deleteTarget.value) {
        router.delete(route('staff.apps.destroy', deleteTarget.value.id), {
            onFinish: () => {
                showDeleteDialog.value = false;
                deleteTarget.value = null;
            },
        });
    }
}
</script>

<template>
    <SiteHeader :title="$t('apps_title')" :subtitle="$t('apps_subtitle')">
        <template #action>
            <Button as-child>
                <Link :href="route('staff.apps.create')">{{ $t('apps_create') }}</Link>
            </Button>
        </template>
    </SiteHeader>

    <div class="px-6 py-4">
        <div v-if="apps.length" class="overflow-hidden rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ $t('apps_name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ $t('apps_client_id') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ $t('apps_created_at') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <tr v-for="app in apps" :key="app.id">
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                            <Link :href="route('staff.apps.show', app.id)" class="hover:underline">{{ app.client_name }}</Link>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 font-mono text-sm text-gray-500">{{ app.client_id }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ app.created_at }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                            <Link :href="route('staff.apps.edit', app.id)" class="mr-4 text-blue-600 hover:underline">{{ $t('apps_edit') }}</Link>
                            <button class="text-red-600 hover:underline" @click="confirmDelete(app)">{{ $t('apps_delete') }}</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="flex items-center justify-center py-24">
            <div class="text-center">
                <p class="mb-4 text-gray-400">{{ $t('apps_no_apps') }}</p>
                <Button as-child>
                    <Link :href="route('staff.apps.create')">{{ $t('apps_create') }}</Link>
                </Button>
            </div>
        </div>
    </div>

    <Dialog v-model:open="showDeleteDialog">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ $t('apps_delete') }}</DialogTitle>
                <DialogDescription>{{ $t('apps_delete_confirm') }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="showDeleteDialog = false">{{ $t('cancel') }}</Button>
                <Button variant="destructive" @click="executeDelete">{{ $t('apps_delete') }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
