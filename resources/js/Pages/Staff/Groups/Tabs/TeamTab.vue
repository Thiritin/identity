<script setup>
import AppLayout from '../../../../Layouts/AppLayout.vue'
import {useForm} from '@inertiajs/vue3'
import {ref, useAttrs} from 'vue'
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import { toast } from 'vue-sonner';
import TabComponent from "./TabComponent.vue";
import TabHeader from "./TabHeader.vue";
import GroupListItem from "../../../../Components/GroupListItem.vue";

const deleteModal = ref({
    visible: false,
    user: {
        id: '',
        name: '',
    },
    groupHash: '',
})

function showDeleteModal(groupHash, user) {
    deleteModal.value = {
        groupHash,
        user,
        visible: true,
    }
}

function deleteUser() {
    form.delete(route('staff.groups.members.destroy', {
        group: deleteModal.value.groupHash,
        member: deleteModal.value.user.id,
    }), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('User removed from group');
        }
    })
    deleteModal.value.visible = false
}

defineOptions({layout: AppLayout})
const props = defineProps({
    group: Object,
    parent: {
        type: Object,
        required: false
    },
    teams: Array,
    myGroups: Array,
    canEdit: Boolean,
})

const attrs = useAttrs()
const form = useForm({})
</script>

<template>
    <div>
        <TabHeader
            :parent="parent"
            :group="group"
            :subtitle="$t('staff_tab_teams')"
            :can-edit="canEdit"
        ></TabHeader>
        <TabComponent active-tab="teams" :group="group"></TabComponent>
        <!-- Department list -->
        <ul role="list" class="divide-y divide-gray-900/5" v-if="teams.length">
            <GroupListItem
                v-for="department in teams"
                :key="department.id"
                :department="department"
                :myGroups="myGroups"
            />
        </ul>
        <!-- No Team created info message -->
        <div v-else class="flex items-center justify-center h-96">
            <p class="text-gray-400">{{ $t('staff_no_teams') }}</p>
        </div>

        <!-- Delete modal -->
        <Dialog v-model:open="deleteModal.visible">
            <DialogContent class="max-w-[25rem]">
                <DialogHeader>
                    <DialogTitle>{{ $t('staff_remove_user_title') }}</DialogTitle>
                </DialogHeader>
                <span class="text-muted-foreground block mb-5">{{ $t('staff_remove_user_confirm', { name: deleteModal.user.name }) }}</span>

                <form action="#" method="post"
                      @submit.prevent="deleteUser">
                    <DialogFooter>
                        <Button type="button"
                                variant="secondary"
                                @click="deleteModal.visible = false">{{ $t('cancel') }}</Button>

                        <Button
                            type="submit"
                            :disabled="form.processing"
                            variant="destructive">
                            {{ $t('staff_remove') }}<span class="sr-only">, {{ deleteModal.user.name }}</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
