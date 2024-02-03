<script setup>
import AppLayout from "../../../Layouts/AppLayout.vue";
import {Head} from "@inertiajs/vue3";
import PageHeadline from "../../../Components/Staff/PageHeadline.vue";
import ListDepartments from "../../../Components/Staff/Departments/ListDepartments.vue";

defineOptions({layout: AppLayout})
const props = defineProps({
    groups: Array,
    myGroups: Array,
})
</script>

<template>
    <div class="xl:grid grid-cols-2 gap-4">
        <div>
            <Head title="Departments"></Head>
            <PageHeadline>My Department(s)</PageHeadline>
            <ListDepartments :departments="myGroups"></ListDepartments>
        </div>
        <div>
            <PageHeadline>All Departments</PageHeadline>
            <ListDepartments :departments="groups"></ListDepartments>
        </div>
    </div>
</template>

<style scoped>

</style>
