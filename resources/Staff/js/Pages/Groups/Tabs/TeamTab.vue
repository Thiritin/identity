<script setup>
import AppLayout from '../../../../../Shared/js/Layouts/AppLayout.vue'
import {useForm} from '@inertiajs/vue3'
import {ref, useAttrs} from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import TabComponent from "./TabComponent.vue";
import TabHeader from "./TabHeader.vue";
import {useToast} from "primevue/usetoast";
import GroupListItem from "../../../../../Shared/js/Components/GroupListItem.vue";

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

const toast = useToast();

function deleteUser() {
    form.delete(route('staff.groups.members.destroy', {
        group: deleteModal.value.groupHash,
        member: deleteModal.value.user.id,
    }), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({severity: 'success', summary: 'Success', detail: 'User removed from group'});
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
            subtitle="Teams"
            :can-edit="canEdit"
        ></TabHeader>
        <TabComponent :active-index="2" :group="group"></TabComponent>
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
            <p class="text-gray-400">No teams created yet.</p>
        </div>

        <!-- Delete modal -->
        <Dialog v-model:visible="deleteModal.visible" modal header="Remove user from department"
                :style="{ width: '25rem' }">
                                <span class="p-text-secondary block mb-5">Really remove {{
                                        deleteModal.user.name
                                    }}?</span>


            <form action="#" method="post"
                  @submit.prevent="deleteUser">
                <div class="flex justify-content-end pull-right gap-2">
                    <Button type="button" label="Cancel"
                            severity="secondary"
                            @click="deleteModal.visible = false"></Button>

                    <Button
                        type="submit"
                        :loading="form.processing"
                        severity="danger">
                        Remove<span class="sr-only">, {{ deleteModal.user.name }}</span>
                    </Button>
                </div>
            </form>

        </Dialog>
    </div>
</template>
