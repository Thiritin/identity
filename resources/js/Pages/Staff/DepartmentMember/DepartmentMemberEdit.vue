<script setup>

import {defineProps} from 'vue';
import AppLayout from "../../../Layouts/AppLayout.vue";
import SiteHeader from "../../../Components/Staff/SiteHeader.vue";
import {Head, useForm} from '@inertiajs/vue3'
import PrimaryButton from "../../../Components/PrimaryButton.vue";
import Dropdown from 'primevue/dropdown';

defineOptions({layout: AppLayout})
const props = defineProps({
    department: Object,
    user: Object,
})
const form = useForm({
    level: 1,
})
const levels = [
    {name: 'Admin', value: 1},
    {name: 'Staff', value: 2},
    {name: 'User', value: 3},
]
</script>

<template>
    <Head title="Edit a member"></Head>
    <SiteHeader :title="department.name + ' - Edit a member'"></SiteHeader>
    <div>
        <div class="max-w-sm mx-auto mt-12">
            <form action="#" method="post"
                  @submit.prevent="form.post(route('staff.departments.members.store',{department: department.hashid}))">
                <div class="mb-4">
                    <!-- Select User Role -->
                    <div class="mb-4">
                        <Dropdown v-model="form.level" :options="levels" optionLabel="name"
                                  class="w-full md:w-[14rem]"/>
                    </div>
                </div>
                <!-- Submit Button -->
                <div class="flex justify-end">
                    <PrimaryButton type="submit" class="btn btn-primary w-full">Add</PrimaryButton>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>

</style>
