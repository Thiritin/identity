<script setup>

import {defineProps} from 'vue';
import AppLayout from "../../../Layouts/AppLayout.vue";
import SiteHeader from "../../../Components/Staff/SiteHeader.vue";
import {Head, useForm} from '@inertiajs/vue3'
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
    {name: 'Member', value: 'member'},
    {name: 'Moderator', value: 'moderator'},
    {name: 'Admin', value: 'admin'},
]
</script>

<template>
    <Head title="Edit a member"></Head>
    <SiteHeader :title="department.name + ' - Edit a member'"></SiteHeader>

    <div>
        <div class="max-w-sm mx-auto mt-12">
            <form action="#" method="post"
                  @submit.prevent="form.post(route('staff.departments.members.store',{department: department.hashid}))">
                <!-- Prime Vue Edit Member form with one select input of level -->
                <Dropdown v-model="form.level" :options="levels" option-value="value" optionLabel="name"
                          class="w-full md:w-[14rem]"/>
            </form>
        </div>
    </div>
</template>

<style scoped>

</style>
