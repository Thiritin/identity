<script setup>
import AppLayout from '../../../../Layouts/AppLayout.vue'
import {router, useForm} from '@inertiajs/vue3'
import {ref, useAttrs} from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import TabComponent from "./TabComponent.vue";
import TabHeader from "./TabHeader.vue";
import {useToast} from "primevue/usetoast";
import Tag from "primevue/tag"

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
    users: Array,
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
            subtitle="Members"
            :can-edit="canEdit"
        ></TabHeader>
        <TabComponent  :active-index="1" :group="group"></TabComponent>
        <!-- User List -->
        <ul role="list" class="divide-y divide-gray-900/5">
            <li v-for="user in users">
                <div class="px-6 py-4">
                    <div class="flex justify-between items-center align-items-center">
                        <div>
                            <div class="text-lg font-semibold">{{ user.name }}</div>
                            <div class="text-sm text-gray-600 flex">
                                <div class="w-16">
                                    {{ user.level }}
                                </div>
                                <div>
                                    <Tag v-for="team in user.teams" severity="secondary">{{ team.name }}</Tag>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2" v-if="canEdit && (user.id !== attrs.user.id || parent !== null)">
                            <Button
                                @click="router.visit(route('staff.groups.members.edit', {group: group.hashid, member: user.id}))"
                                severity="secondary"
                                size="small"
                            >Edit<span class="sr-only">, {{ user.name }}</span></Button>

                            <Button
                                severity="danger"
                                size="small"
                                @click="showDeleteModal(group.hashid, user)"
                                :aria-controls="deleteModal.visible ? 'dlg' : null"
                                :aria-expanded="!!deleteModal.visible">Remove<span
                                class="sr-only">, {{ user.name }}</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </li>
        </ul>

        <!-- Delete modal -->
        <Dialog v-model:visible="deleteModal.visible" modal header="Remove user from group"
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
