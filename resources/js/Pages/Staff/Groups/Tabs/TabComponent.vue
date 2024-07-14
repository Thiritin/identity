<script setup>
import TabMenu from "primevue/tabmenu";
import {router} from "@inertiajs/vue3";

const props = defineProps({
    activeIndex: {
        type: Number,
        required: true
    },
    group: {
        type: Object,
        required: true
    }
});
const tabMenuItems = [
    {label: 'Info', icon: 'pi pi-box', command: () => router.visit(route('staff.groups.show', {group: props.group.hashid}))},
    {label: 'Members', icon: 'pi pi-users', command: () => router.visit(route('staff.groups.members.index', {group: props.group.hashid}))},
];
</script>

<template>
    <TabMenu :activeIndex="activeIndex" :model="tabMenuItems"></TabMenu>
</template>

<style scoped>

</style>
