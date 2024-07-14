<script setup>

import SiteHeader from "../../../../Components/Staff/SiteHeader.vue";
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import InputText from "primevue/inputtext";
import {Head, usePage} from "@inertiajs/vue3";
import Dialog from 'primevue/dialog';
import {ref, watch} from "vue";
import SelectButton from 'primevue/selectbutton';
import {useForm} from "laravel-precognition-vue-inertia";
import {useToast} from "primevue/usetoast";


const props = defineProps({
    group: Object,
    canEdit: Boolean,
    subtitle: String,
})
const showAddMemberDialog = ref(false);
const toast = useToast();

function submit() {
    if (addVia.value === 'Staff List') {
        form.email = '';
    } else {
        form.user_id = '';
    }
    form.submit({
        preserveScroll: true,
        onSuccess: () => {
            showAddMemberDialog.value = false;
            toast.add({severity: 'success', summary: 'Success', detail: 'User added to group'});
        }
    });
}
const staffMemberList = usePage().props.staffMemberList;

const form = useForm('POST',route('staff.groups.members.store',{group: props.group.hashid}), {
    user_id: '',
    email: '',
})

// watch showAddMemberDialog if closed, reset form
watch(showAddMemberDialog, (value) => {
    if (!value) {
        form.reset();
    }
})

// If email is selected, reset user_id
watch(() => form.email, (value) => {
    if (value) {
        form.user_id = '';
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
        <!-- Template Action -->
        <template v-slot:action v-if="canEdit">
            <div class="flex gap-2">
                <Button
                    size="small"
                    @click="showAddMemberDialog = true">Add User
                </Button>
            </div>
        </template>
    </SiteHeader>
    <Dialog v-model:visible="showAddMemberDialog" modal class="max-w-md" header="Add Member">
        <form @submit.prevent="submit" class="space-y-3">
            <p>You can add existing <strong>Staff Members</strong> via the Dropdown to this Group. Non-Staff Members need to be added using the E-Mail.</p>
            <SelectButton class="w-full grid grid-cols-2" v-model="addVia" :unselectable="false" :options="options" aria-labelledby="basic" />
            <!-- Dropdown -->
            <div v-if="staffMemberList.length && addVia === 'Staff List'">
                <label for="user_id">Staff Member</label>
                <Dropdown
                    v-model="form.user_id"
                    :options="staffMemberList"
                    optionLabel="name"
                    optionValue="id"
                    :virtualScrollerOptions="{ itemSize: 38 }"
                    placeholder="Select a Staff Member"
                    filter
                    :invalid="form.errors.user_id"
                    class="w-full"/>
                <small class="text-red-500" v-if="form.errors.user_id">{{ form.errors.user_id }}</small>
            </div>
            <!-- E-Mail -->
            <div v-if="addVia === 'Email'">
                <label for="email">E-Mail</label>
                <InputText :invalid="form.errors.email" v-model="form.email" type="email" id="email" class="w-full"/>
                <small class="text-red-500" v-if="form.errors.email">{{ form.errors.email }}</small>
            </div>
            <!-- Submit Button -->
            <div class="flex justify-end">
                <Button type="submit" class="btn btn-primary w-full">Add</Button>
            </div>
        </form>
    </Dialog>
</template>

<style scoped>

</style>
