<script setup>
import AppLayout from '../../../Layouts/AppLayout.vue'
import BaseButton from '../../../Components/BaseButton.vue'
import {Head, useForm} from '@inertiajs/vue3'
import SiteHeader from '../../../Components/Staff/SiteHeader.vue'
import {ref, useAttrs} from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import PrimaryButton from '../../../Components/PrimaryButton.vue'


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
    form.delete(route('staff.departments.members.destroy', {
        department: deleteModal.value.groupHash,
        member: deleteModal.value.user.id,
    }))
    deleteModal.value.visible = false
}

defineOptions({layout: AppLayout})
const props = defineProps({
    group: Object,
    users: Array,
    canEdit: Boolean,
})

const attrs = useAttrs()
const form = useForm({})
</script>

<template>
    <div>
        <Head :title="group.name"></Head>
        <SiteHeader :title="group.name">
            <!-- Template Action -->
            <template v-slot:action v-if="canEdit">
                <div class="flex gap-2">
                    <BaseButton
                        :href="route('staff.departments.members.create',{department: group.hashid})"
                        small
                        info>Add User
                    </BaseButton>
                    <!-- @Todo Add Edit Department
                    <BaseButton small primary :href="route('staff.departments.edit',{department: group.id})">Edit<span
                        class="sr-only">, department</span>
                    </BaseButton> -->
                </div>
            </template>
        </SiteHeader>
        <!-- User List -->
        <ul role="list" class="divide-y divide-gray-900/5">
            <li v-for="user in users">
                <div class="px-6 py-4">
                    <div class="flex justify-between items-center align-items-center">
                        <div>
                            <div class="text-lg font-semibold">{{ user.name }}</div>
                            <div class="text-sm text-gray-600">{{ user.level }}</div>
                        </div>
                        <div class="flex gap-2" v-if="canEdit && user.id !== attrs.user.id">
                            <BaseButton
                                :href="route('staff.departments.members.edit', {department: group.hashid, member: user.id})"
                                class="text-primary-600 font-semibold hover:text-primary-900"
                            >Edit<span class="sr-only">, {{ user.name }}</span></BaseButton>

                            <BaseButton class="text-primary-600 font-semibold hover:text-primary-900"
                                        @click="showDeleteModal(group.hashid, user)"
                                        :aria-controls="deleteModal.visible ? 'dlg' : null"
                                        :aria-expanded="!!deleteModal.visible">Remove<span
                                class="sr-only">, {{ user.name }}</span>
                            </BaseButton>
                        </div>
                    </div>
                </div>
            </li>
        </ul>

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

                    <PrimaryButton type="submit" class="btn btn-primary">
                        Remove<span class="sr-only">, {{ deleteModal.user.name }}</span>
                    </PrimaryButton>
                </div>
            </form>

        </Dialog>
    </div>
</template>
