<script setup>

import SiteHeader from "../../../../Components/Staff/SiteHeader.vue";
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import InputText from "primevue/inputtext";
import {Head, Link, usePage} from "@inertiajs/vue3";
import Dialog from 'primevue/dialog';
import {ref, watch} from "vue";
import SelectButton from 'primevue/selectbutton';
import {useForm} from "laravel-precognition-vue-inertia";
import {useToast} from "primevue/usetoast";

const props = defineProps({
    group: Object,
    parent: Object,
    canEdit: Boolean,
    subtitle: String,
})
const showAddMemberDialog = ref(false);
const showAddTeamDialog = ref(false);
const confirmDeleteTeamDialog = ref(false);
const showEditTeamDialog = ref(false);
const toast = useToast();

function submitUserForm() {
    if (addVia.value === 'Staff List') {
        addUserForm.email = '';
    } else {
        addUserForm.user_id = '';
    }
    addUserForm.submit({
        preserveScroll: true,
        onSuccess: () => {
            showAddMemberDialog.value = false;
            toast.add({severity: 'success', summary: 'Success', detail: 'User added to group'});
        }
    });
}

function submitTeamForm() {
    addTeamForm.submit({
        preserveScroll: true,
        onSuccess: () => {
            showAddTeamDialog.value = false;
            addTeamForm.reset();
            toast.add({severity: 'success', summary: 'Success', detail: 'Team added to department'});
        }
    });
}

function submitEditTeamForm() {
    editTeamForm.submit({
        preserveScroll: true,
        onSuccess: () => {
            showEditTeamDialog.value = false;
            toast.add({severity: 'success', summary: 'Success', detail: 'Team updated'});
        }
    });
}

const staffMemberList = usePage().props.staffMemberList;

const addUserForm = useForm('POST',route('staff.groups.members.store',{group: props.group.hashid}), {
    user_id: '',
    email: '',
})

const addTeamForm = useForm('POST',route('staff.groups.teams.store',{group: props.group.hashid}), {
    name: '',
})

const editTeamForm = useForm('PATCH', route('staff.groups.update', {group: props.group.hashid}), {
    name: props.group.name,
})

// watch showAddMemberDialog if closed, reset addUserForm
watch(showAddMemberDialog, (value) => {
    if (!value) {
        addUserForm.reset();
    }
})

// If email is selected, reset user_id
watch(() => addUserForm.email, (value) => {
    if (value) {
        addUserForm.user_id = '';
    }
})
const addVia = ref('Staff List');
const options = ref(['Staff List', 'Email']);
</script>

<template>
    <Head :title="subtitle+' - '+ group.name"></Head>
    <SiteHeader
        :title="group.name"
        :subtitle="subtitle"
    >
        <template #title>
            <h1 class="font-semibold text-xl flex items-center">
                <Link as="span" :href="route('staff.groups.show', {'group': parent.hashid})" v-if="parent" class="cursor-pointer">{{ parent.name }}</Link><span class="px-2 text-2xl" v-if="parent">/</span>{{ group.name }}
            </h1>
        </template>
        <!-- Template Action -->
        <template v-slot:action v-if="canEdit">
            <div class="flex gap-2">
                <Button
                    size="small"
                    @click="showEditTeamDialog = true">Edit Team</Button>
                <Button
                    size="small"
                    @click="showAddMemberDialog = true">Add User
                </Button>
                <Button
                    v-if="group.parent_id === null"
                    size="small"
                    outlined
                    @click="showAddTeamDialog = true">Add Team
                </Button>
                <Button
                    v-if="group.parent_id !== null"
                    size="small"
                    severity="danger"
                    outlined
                    @click="confirmDeleteTeamDialog = true">Delete Team
                </Button>
            </div>
        </template>
    </SiteHeader>
    <!-- Add Member Dialog -->
    <Dialog v-model:visible="showAddMemberDialog" modal class="max-w-md" header="Add Member">
        <form @submit.prevent="submitUserForm" class="space-y-3">
            <p>You can add existing <strong>Staff Members</strong> via the Dropdown to this Group. Non-Staff Members need to be added using the E-Mail.</p>
            <SelectButton class="w-full grid grid-cols-2" v-model="addVia" :unselectable="false" :options="options" aria-labelledby="basic" />
            <!-- Dropdown -->
            <div v-if="staffMemberList.length && addVia === 'Staff List'">
                <label for="user_id">Staff Member</label>
                <Dropdown
                    v-model="addUserForm.user_id"
                    :options="staffMemberList"
                    optionLabel="name"
                    optionValue="id"
                    :virtualScrollerOptions="{ itemSize: 38 }"
                    placeholder="Select a Staff Member"
                    filter
                    :invalid="addUserForm.errors.user_id"
                    class="w-full"/>
                <small class="text-red-500" v-if="addUserForm.errors.user_id">{{ addUserForm.errors.user_id }}</small>
            </div>
            <!-- E-Mail -->
            <div v-if="addVia === 'Email'">
                <label for="email">E-Mail</label>
                <InputText :invalid="addUserForm.errors.email" v-model="addUserForm.email" type="email" id="email" class="w-full"/>
                <small class="text-red-500" v-if="addUserForm.errors.email">{{ addUserForm.errors.email }}</small>
            </div>
            <!-- Submit Button -->
            <div class="flex justify-end">
                <Button type="submit" class="btn btn-primary w-full">Add Member</Button>
            </div>
        </form>
    </Dialog>
    <!-- Create a new Team Dialog  -->
    <Dialog v-model:visible="showAddTeamDialog" modal class="max-w-md" header="Create a new Team">
        <form @submit.prevent="submitTeamForm()" class="space-y-3">
            <label for="name">Team Name</label>
            <InputText :invalid="addTeamForm.errors.name" v-model="addTeamForm.name" id="name" class="w-full"/>
            <small class="text-red-500" v-if="addTeamForm.errors.name">{{ addTeamForm.errors.name }}</small>
            <!-- Submit Button -->
            <div class="flex justify-end">
                <Button type="submit" class="btn btn-primary w-full">Create Team</Button>
            </div>
        </form>
    </Dialog>
    <!-- Delete Team Dialog -->
    <Dialog v-model:visible="confirmDeleteTeamDialog" modal class="max-w-md" header="Delete Team">
        <p>Are you sure you want to delete this Team?</p>
        <div class="flex justify-end gap-2">
            <Button
                size="small"
                @click="confirmDeleteTeamDialog = false">Cancel
            </Button>
            <Button
                size="small"
                severity="danger"
                @click="useForm('DELETE', route('staff.groups.destroy', {group: group.hashid}),{}).submit({
                    preserveScroll: true,
                    onSuccess: () => {
                        confirmDeleteTeamDialog = false;
                        toast.add({severity: 'success', summary: 'Success', detail: 'Team deleted'});
                    }
                })">Delete
            </Button>
        </div>
    </Dialog>
    <!-- Edit Team Name -->
    <Dialog v-model:visible="showEditTeamDialog" modal class="max-w-md" header="Edit Team Name">
        <form @submit.prevent="submitEditTeamForm()" class="space-y-3">
            <label for="name">Team Name</label>
            <InputText :invalid="editTeamForm.errors.name" v-model="editTeamForm.name" id="name" class="w-full"/>
            <small class="text-red-500" v-if="editTeamForm.errors.name">{{ editTeamForm.errors.name }}</small>
            <!-- Submit Button -->
            <div class="flex justify-end">
                <Button type="submit" class="btn btn-primary w-full">Save</Button>
            </div>
        </form>
    </Dialog>
</template>

<style scoped>

</style>
