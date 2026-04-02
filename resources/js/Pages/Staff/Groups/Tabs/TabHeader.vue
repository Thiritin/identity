<script setup>

import SiteHeader from "../../../../Components/Staff/SiteHeader.vue";
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import { toast } from 'vue-sonner';
import {Head, Link, usePage} from "@inertiajs/vue3";
import {ref, watch} from "vue";
import {useForm} from "@inertiajs/vue3";

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

function submitUserForm() {
    if (addVia.value === 'staff') {
        addUserForm.email = '';
    } else {
        addUserForm.user_id = '';
    }
    addUserForm.submit({
        preserveScroll: true,
        onSuccess: () => {
            showAddMemberDialog.value = false;
            toast.success('User added to group');
        }
    });
}

function submitTeamForm() {
    addTeamForm.submit({
        preserveScroll: true,
        onSuccess: () => {
            showAddTeamDialog.value = false;
            addTeamForm.reset();
            toast.success('Team added to department');
        }
    });
}

function submitEditTeamForm() {
    editTeamForm.submit({
        preserveScroll: true,
        onSuccess: () => {
            showEditTeamDialog.value = false;
            toast.success('Team updated');
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
const addVia = ref('staff');
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
                    size="sm"
                    @click="showEditTeamDialog = true">Edit Team</Button>
                <Button
                    size="sm"
                    @click="showAddMemberDialog = true">Add User
                </Button>
                <Button
                    v-if="group.parent_id === null"
                    size="sm"
                    variant="outline"
                    @click="showAddTeamDialog = true">Add Team
                </Button>
                <Button
                    v-if="group.parent_id !== null"
                    size="sm"
                    variant="destructive"
                    @click="confirmDeleteTeamDialog = true">Delete Team
                </Button>
            </div>
        </template>
    </SiteHeader>
    <!-- Add Member Dialog -->
    <Dialog v-model:open="showAddMemberDialog">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Add Member</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="submitUserForm" class="space-y-3">
                <p>You can add existing <strong>Staff Members</strong> via the Dropdown to this Group. Non-Staff Members need to be added using the E-Mail.</p>
                <ToggleGroup type="single" :model-value="addVia" @update:model-value="(v) => { if (v) addVia = v }" class="w-full grid grid-cols-2">
                    <ToggleGroupItem value="staff">Staff List</ToggleGroupItem>
                    <ToggleGroupItem value="email">Email</ToggleGroupItem>
                </ToggleGroup>
                <!-- Select -->
                <div v-if="staffMemberList.length && addVia === 'staff'">
                    <label for="user_id">Staff Member</label>
                    <Select v-model="addUserForm.user_id">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Select a Staff Member" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="member in staffMemberList" :key="member.id" :value="String(member.id)">
                                {{ member.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <small class="text-red-500" v-if="addUserForm.errors.user_id">{{ addUserForm.errors.user_id }}</small>
                </div>
                <!-- E-Mail -->
                <div v-if="addVia === 'email'">
                    <label for="email">E-Mail</label>
                    <Input v-model="addUserForm.email" type="email" id="email" class="w-full"/>
                    <small class="text-red-500" v-if="addUserForm.errors.email">{{ addUserForm.errors.email }}</small>
                </div>
                <!-- Submit Button -->
                <DialogFooter>
                    <Button type="submit" class="w-full">Add Member</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
    <!-- Create a new Team Dialog  -->
    <Dialog v-model:open="showAddTeamDialog">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Create a new Team</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="submitTeamForm()" class="space-y-3">
                <label for="name">Team Name</label>
                <Input v-model="addTeamForm.name" id="name" class="w-full"/>
                <small class="text-red-500" v-if="addTeamForm.errors.name">{{ addTeamForm.errors.name }}</small>
                <!-- Submit Button -->
                <DialogFooter>
                    <Button type="submit" class="w-full">Create Team</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
    <!-- Delete Team Dialog -->
    <Dialog v-model:open="confirmDeleteTeamDialog">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Delete Team</DialogTitle>
            </DialogHeader>
            <p>Are you sure you want to delete this Team?</p>
            <DialogFooter>
                <Button
                    size="sm"
                    variant="secondary"
                    @click="confirmDeleteTeamDialog = false">Cancel
                </Button>
                <Button
                    size="sm"
                    variant="destructive"
                    @click="useForm('DELETE', route('staff.groups.destroy', {group: group.hashid}),{}).submit({
                        preserveScroll: true,
                        onSuccess: () => {
                            confirmDeleteTeamDialog = false;
                            toast.success('Team deleted');
                        }
                    })">Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
    <!-- Edit Team Name -->
    <Dialog v-model:open="showEditTeamDialog">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Edit Team Name</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="submitEditTeamForm()" class="space-y-3">
                <label for="name">Team Name</label>
                <Input v-model="editTeamForm.name" id="name" class="w-full"/>
                <small class="text-red-500" v-if="editTeamForm.errors.name">{{ editTeamForm.errors.name }}</small>
                <!-- Submit Button -->
                <DialogFooter>
                    <Button type="submit" class="w-full">Save</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
