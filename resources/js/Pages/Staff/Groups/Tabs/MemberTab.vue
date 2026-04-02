<script setup>
import AppLayout from '../../../../Layouts/AppLayout.vue'
import {router, useForm} from '@inertiajs/vue3'
import {ref, useAttrs} from 'vue'
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import { toast } from 'vue-sonner';
import TabComponent from "./TabComponent.vue";
import TabHeader from "./TabHeader.vue";

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
        <TabComponent active-tab="members" :group="group"></TabComponent>
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
                                    <Badge v-for="team in user.teams" variant="secondary">{{ team.name }}</Badge>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2" v-if="canEdit && (user.id !== attrs.user.id || parent !== null)">
                            <Button
                                @click="router.visit(route('staff.groups.members.edit', {group: group.hashid, member: user.id}))"
                                variant="secondary"
                                size="sm"
                            >Edit<span class="sr-only">, {{ user.name }}</span></Button>

                            <Button
                                variant="destructive"
                                size="sm"
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
        <Dialog v-model:open="deleteModal.visible">
            <DialogContent class="max-w-[25rem]">
                <DialogHeader>
                    <DialogTitle>Remove user from group</DialogTitle>
                </DialogHeader>
                <span class="text-muted-foreground block mb-5">Really remove {{
                        deleteModal.user.name
                    }}?</span>

                <form action="#" method="post"
                      @submit.prevent="deleteUser">
                    <DialogFooter>
                        <Button type="button"
                                variant="secondary"
                                @click="deleteModal.visible = false">Cancel</Button>

                        <Button
                            type="submit"
                            :disabled="form.processing"
                            variant="destructive">
                            Remove<span class="sr-only">, {{ deleteModal.user.name }}</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
